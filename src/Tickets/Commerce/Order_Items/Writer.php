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
use TEC\Tickets\Commerce\Order_Items\Repositories\Order_Items;
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
	 * @var Order_Items
	 */
	private Order_Items $repository;

	/**
	 * Writer constructor.
	 *
	 * @since TBD
	 *
	 * @param Line_Item_Types $types      The line item types that turn order items into rows.
	 * @param Order_Items     $repository The Order Items repository.
	 */
	public function __construct( Line_Item_Types $types, Order_Items $repository ) {
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
			$currency = get_post_meta( $order_id, Order::$currency_meta_key, true ) ?: Currency::get_currency_code();
			$rows     = [];
			$position = 0;

			foreach ( $items as $key => $item ) {
				$rows[] = $this->types->get_for_item( $item )::to_row( $key, $item, $order_id, $currency ) + [ 'position' => $position++ ];
			}

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
}
