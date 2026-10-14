<?php
/**
 * Writes the lines of new Tickets Commerce orders to the Order Items table.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Items
 */

namespace TEC\Tickets\Commerce\Order_Items;

use InvalidArgumentException;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Order_Items\Line_Items\Line_Item_Types;
use TEC\Tickets\Commerce\Order_Items\Repositories\Order_Items_Repository;
use TEC\Tickets\Commerce\Order_Items\Traits\With_Logger;
use TEC\Tickets\Commerce\Ticket;
use TEC\Tickets\Commerce\Utils\Currency;
use TEC\Tickets\RSVP\V2\Constants;
use Throwable;
use Tribe__Log as Log;

/**
 * Class Writer.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Items
 */
final class Writer {
	use With_Logger;

	/**
	 * The meta key that marks an order whose lines are stored in the table.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const VERSION_META_KEY = '_tec_tc_order_items_version';

	/**
	 * The storage version of an order whose lines are stored in the table.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	public const VERSION = 2;

	/**
	 * The prefix of the version meta value of an order whose rows are being synced.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	private const SYNCING = 'syncing:';

	/**
	 * The line item types that turn order items into rows.
	 *
	 * @since TBD
	 *
	 * @var Line_Item_Types
	 */
	private Line_Item_Types $types;

	/**
	 * The Order Items repository.
	 *
	 * @since TBD
	 *
	 * @var Order_Items_Repository
	 */
	private Order_Items_Repository $repository;

	/**
	 * Writer constructor.
	 *
	 * @since TBD
	 *
	 * @param Line_Item_Types        $types      The line item types that turn order items into rows.
	 * @param Order_Items_Repository $repository The Order Items repository.
	 */
	public function __construct( Line_Item_Types $types, Order_Items_Repository $repository ) {
		$this->types      = $types;
		$this->repository = $repository;
	}

	/**
	 * Writes one row per item of a newly created order, then marks the order as stored in the table.
	 *
	 * Runs on the payment path, so any failure is logged, never thrown: the order then keeps
	 * reading its items from the order meta.
	 *
	 * @since TBD
	 *
	 * @param int $order_id The order ID.
	 *
	 * @return void
	 */
	public function write_created_order( int $order_id ): void {
		$items = get_post_meta( $order_id, Order::$items_meta_key, true );

		if ( ! is_array( $items ) || ! $items ) {
			return;
		}

		try {
			$rows = $this->to_rows( $order_id, $items );

			$this->assert_unique_identities( $rows );

			/*
			 * A new order owns no rows yet. Any found carry a recycled post ID, e.g. after the posts table was
			 * truncated by `wp site empty`, and would otherwise be read back as this order's lines.
			 */
			$this->repository->delete_by_order( $order_id );

			/*
			 * One INSERT is atomic on its own; opening a transaction here would commit a caller's,
			 * e.g. Square's duplicate-order check.
			 */
			$this->repository->insert_many( $rows );
		} catch ( Throwable $e ) {
			$this->log(
				Log::ERROR,
				'The order items could not be written to the table; the order keeps them in its meta.',
				[
					'order_id' => $order_id,
					'error'    => $e->getMessage(),
				]
			);

			$this->snapshot_tickets( $order_id, $items );

			return;
		}

		update_post_meta( $order_id, self::VERSION_META_KEY, self::VERSION );
		// The order model is cached until the post is modified, and a meta write does not modify it.
		clean_post_cache( $order_id );
	}

	/**
	 * Syncs the rows of an order stored in the table with its new items, matching lines by identity so kept lines
	 * keep their row IDs and take their new place in the list.
	 *
	 * Orders not stored in the table are left alone. The order's mark is swapped for a syncing token first and set
	 * back only once every statement succeeded, so a sync that fails or dies half way leaves it reading the new items
	 * from the order meta. A sync that finds another's token unmarks the order: the items changed while the other
	 * sync ran, and its rows no longer match them. Runs on the payment path, so any failure is logged, never thrown.
	 *
	 * @since TBD
	 *
	 * @param int   $order_id The order ID.
	 * @param array $items    The order's new items, as saved to the order meta.
	 *
	 * @return void
	 */
	public function sync_updated_order( int $order_id, array $items ): void {
		$mark = get_post_meta( $order_id, self::VERSION_META_KEY, true );

		if ( is_string( $mark ) && str_starts_with( $mark, self::SYNCING ) ) {
			// Updated, not deleted: the other sync re-marks the order only if its token is still there.
			update_post_meta( $order_id, self::VERSION_META_KEY, '' );
			clean_post_cache( $order_id );

			return;
		}

		if ( self::VERSION !== absint( $mark ) ) {
			return;
		}

		$token = self::SYNCING . wp_generate_uuid4();

		// Compare and set: a writer that changed the mark since it was read keeps it.
		if ( ! update_post_meta( $order_id, self::VERSION_META_KEY, $token, self::VERSION ) ) {
			return;
		}

		try {
			$rows    = $this->to_rows( $order_id, $items );
			$stored  = [];
			$updates = [];
			$inserts = [];

			foreach ( $this->repository->get_by_order( $order_id ) as $model ) {
				$row = $model->toArray();

				$stored[ $this->line_identity( $row ) ] = $row;
			}

			foreach ( $rows as $key => $row ) {
				$identity = $this->line_identity( $row );
				$current  = $stored[ $identity ] ?? null;

				if ( null === $current ) {
					$inserts[] = $row;
					continue;
				}

				unset( $stored[ $identity ] );

				// serialize() is strict on key order and scalar types, and compares objects by class and state.
				if ( $current['position'] !== $row['position'] || $current['currency'] !== $row['currency'] || serialize( $this->types->get( $current['type'] )::from_row( $current ) ) !== serialize( [ (string) $key, $items[ $key ] ] ) ) { // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
					$updates[] = [ 'id' => $current['id'] ] + $this->keep_purchase_details( $row, $current, $items[ $key ] );
				}
			}

			// No transaction: opening one would commit a caller's, e.g. Order::modify_status(). The unmarked order covers a partial sync.
			$this->repository->delete_many( array_column( $stored, 'id' ) );
			$this->repository->update_rows( $updates );
			$this->repository->insert_many( $inserts );

			// Compare and set: a newer sync that replaced the token leaves the order unmarked.
			update_post_meta( $order_id, self::VERSION_META_KEY, self::VERSION, $token );
		} catch ( Throwable $e ) {
			// Updated, not deleted: with no row to compare against, another sync's compare and set would add the mark back.
			update_post_meta( $order_id, self::VERSION_META_KEY, '' );

			try {
				$this->repository->delete_by_order( $order_id );
			} catch ( Throwable $ignored ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch -- The order is unmarked, so rows left behind are never read.
			}

			$this->log(
				Log::DEBUG,
				'The order items could not be synced to the table; the order reads them from its meta.',
				[
					'order_id' => $order_id,
					'error'    => $e->getMessage(),
				]
			);
		}

		clean_post_cache( $order_id );
	}

	/**
	 * Deletes the rows of a permanently deleted order.
	 *
	 * A failure is logged, never thrown, so it cannot stop the order from being deleted. It is a warning, not a
	 * debug entry: nothing cleans these rows up later, so the log is the only trace of them.
	 *
	 * @since TBD
	 *
	 * @param int $order_id The order ID.
	 *
	 * @return void
	 */
	public function delete_order_rows( int $order_id ): void {
		try {
			$this->repository->delete_by_order( $order_id );
		} catch ( Throwable $e ) {
			$this->log(
				Log::WARNING,
				'The order items could not be deleted from the table.',
				[
					'order_id' => $order_id,
					'error'    => $e->getMessage(),
				]
			);
		}
	}

	/**
	 * Keeps the name and type of each ticket on the items of an order that stays in the order meta.
	 *
	 * Without a row to read them from, a ticket deleted later would leave the order screens only its ID. The
	 * `snapshot` entry is where the order screens look next.
	 *
	 * @since TBD
	 *
	 * @param int   $order_id The order ID.
	 * @param array $items    The order's items, as saved to the order meta.
	 *
	 * @return void
	 */
	private function snapshot_tickets( int $order_id, array $items ): void {
		try {
			foreach ( $items as $key => $item ) {
				$ticket = is_array( $item ) && ! empty( $item['ticket_id'] ) && $this->is_ticket_item( $item )
					? tribe( Ticket::class )->get_ticket( $item['ticket_id'] )
					: null;

				if ( $ticket ) {
					$items[ $key ]['snapshot'] = [
						'name'        => $ticket->name,
						'ticket_type' => $ticket->type(),
					];
				}
			}

			// update_post_meta() unslashes its value, which would strip the backslashes of a ticket name.
			update_post_meta( $order_id, Order::$items_meta_key, wp_slash( $items ) );
		} catch ( Throwable $e ) {
			$this->log(
				Log::DEBUG,
				'The ticket names could not be kept on the order items.',
				[
					'order_id' => $order_id,
					'error'    => $e->getMessage(),
				]
			);
		}
	}

	/**
	 * Rejects rows that share a line identity, which the table's unique key would refuse as a whole.
	 *
	 * Lines with no ticket, modifier or rule ID, such as the several taxes of a Square order, all store the same
	 * identity, so an order holding two of them stays in the order meta, by this decision and not by a failed query.
	 *
	 * @since TBD
	 *
	 * @param array<int,array<string,mixed>> $rows The rows of an order.
	 *
	 * @return void
	 *
	 * @throws InvalidArgumentException When two rows have the same ticket, modifier and purchase rule IDs.
	 */
	private function assert_unique_identities( array $rows ): void {
		$keys = [];

		foreach ( $rows as $row ) {
			$identity = $row['ticket_id'] . '-' . $row['modifier_id'] . '-' . $row['purchase_rule_id'];

			if ( isset( $keys[ $identity ] ) ) {
				throw new InvalidArgumentException( sprintf( 'Items "%s" and "%s" share one line identity (ticket, modifier and rule IDs), which the table cannot tell apart.', $keys[ $identity ], $row['item_key'] ) );
			}

			$keys[ $identity ] = $row['item_key'];
		}
	}

	/**
	 * Whether an order item is a ticket line, as opposed to a fee, a coupon or a discount.
	 *
	 * @since TBD
	 *
	 * @param array $item The order item.
	 *
	 * @return bool
	 */
	private function is_ticket_item( array $item ): bool {
		return in_array( $item['type'] ?? '', [ '', 'ticket', Constants::TC_RSVP_TYPE ], true );
	}

	/**
	 * Maps an order's items to table rows, positioned in list order, without `id` and `created_at`.
	 *
	 * @since TBD
	 *
	 * @param int   $order_id The order ID.
	 * @param array $items    The order items.
	 *
	 * @return array<int|string,array<string,mixed>> The rows, keyed like the items.
	 */
	private function to_rows( int $order_id, array $items ): array {
		$currency = get_post_meta( $order_id, Order::$currency_meta_key, true ) ?: Currency::get_currency_code();
		$rows     = [];
		$position = 0;

		foreach ( $items as $key => $item ) {
			$rows[ $key ] = $this->types->get_for_item( $item )::to_row( $key, $item, $order_id, $currency ) + [ 'position' => $position++ ];
		}

		return $rows;
	}

	/**
	 * Carries the purchase-time details of a stored line over to its updated row.
	 *
	 * The details are read from the live ticket and event when a row is built; a line that is kept must not
	 * take the ticket's or event's current values in place of the ones it was bought with.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $row     The updated row.
	 * @param array<string,mixed> $current The stored row.
	 * @param array               $item    The line's new item.
	 *
	 * @return array<string,mixed> The updated row, holding the stored details.
	 */
	private function keep_purchase_details( array $row, array $current, array $item ): array {
		$kept = [ 'sku', 'ticket_type' ];

		// A name set on the item itself is the item's to change.
		if ( ! isset( $item['display_name'] ) ) {
			$kept[] = 'name';
		}

		if ( $current['event_id'] === $row['event_id'] ) {
			array_push( $kept, 'event_title', 'event_start_date', 'event_start_date_utc' );
		}

		return array_merge( $row, array_intersect_key( $current, array_flip( $kept ) ) );
	}

	/**
	 * Returns the line identity of a row: the columns of the table's unique order line index, but the order.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $row The row.
	 *
	 * @return string The line identity.
	 */
	private function line_identity( array $row ): string {
		return "{$row['ticket_id']}:{$row['modifier_id']}:{$row['purchase_rule_id']}";
	}
}
