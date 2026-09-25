<?php
/**
 * Writes the lines of new Tickets Commerce orders to the Order Items table.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Items
 */

namespace TEC\Tickets\Commerce\Order_Items;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\lucatume\DI52\Container;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Order_Items\Line_Items\Line_Item_Types;
use TEC\Tickets\Commerce\Order_Items\Repositories\Order_Items;
use TEC\Tickets\Commerce\Utils\Currency;
use Throwable;

/**
 * Class Writer.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Items
 */
class Writer extends Controller_Contract {
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
	 * @param Container       $container  The container.
	 * @param Line_Item_Types $types      The line item types that turn order items into rows.
	 * @param Order_Items     $repository The Order Items repository.
	 */
	public function __construct( Container $container, Line_Item_Types $types, Order_Items $repository ) {
		parent::__construct( $container );
		$this->types      = $types;
		$this->repository = $repository;
	}

	/**
	 * Unhooks the writer.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'tec_tickets_commerce_order_created', [ $this, 'write_created_order' ] );
		remove_action( 'tec_tickets_commerce_order_updated', [ $this, 'sync_updated_order' ] );
	}

	/**
	 * Writes one row per item of a newly created order, then marks the order as stored in the table.
	 *
	 * Runs on the payment path, so any failure is logged, never thrown: the order then keeps
	 * reading its items from the order meta.
	 *
	 * @since TBD
	 *
	 * @param int   $order_id The order ID.
	 * @param array $items    The order items, as saved to the order meta.
	 *
	 * @return void
	 */
	public function write_created_order( int $order_id, array $items ): void {
		if ( ! $items ) {
			return;
		}

		try {
			$rows = $this->to_rows( $order_id, $items );

			/*
			 * A new order owns no rows yet. Any found carry a recycled post ID, e.g. after the posts table was
			 * truncated by `wp site empty`, and would otherwise be read back as this order's lines.
			 */
			$this->repository->delete_by_order( $order_id );
			// One INSERT is atomic on its own; opening a transaction here would commit a caller's, e.g. Square's duplicate-order check.
			$this->repository->insert_many( $rows );
		} catch ( Throwable $e ) {
			$this->debug(
				'The order items could not be written to the table; the order keeps them in its meta.',
				[
					'order_id' => $order_id,
					'error'    => $e->getMessage(),
				]
			);

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
	 * Orders not stored in the table are left alone. The order is unmarked first and marked again only once every
	 * statement succeeded, so a sync that fails or dies half way leaves it reading the new items from the order meta.
	 * Runs on the payment path, so any failure is logged, never thrown.
	 *
	 * @since TBD
	 *
	 * @param int   $order_id The order ID.
	 * @param array $items    The order's new items, as saved to the order meta.
	 *
	 * @return void
	 */
	public function sync_updated_order( int $order_id, array $items ): void {
		if ( self::VERSION !== absint( get_post_meta( $order_id, self::VERSION_META_KEY, true ) ) ) {
			return;
		}

		delete_post_meta( $order_id, self::VERSION_META_KEY );

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
				if ( $current['position'] !== $row['position'] || $current['currency'] !== $row['currency'] || serialize( $this->types->get( $current['type'] )->from_row( $current ) ) !== serialize( [ (string) $key, $items[ $key ] ] ) ) { // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
					$updates[] = [ 'id' => $current['id'] ] + $row;
				}
			}

			// No transaction: opening one would commit a caller's, e.g. Order::modify_status(). The unmarked order covers a partial sync.
			$this->repository->delete_many( array_column( $stored, 'id' ) );
			$this->repository->update_rows( $updates );
			$this->repository->insert_many( $inserts );

			update_post_meta( $order_id, self::VERSION_META_KEY, self::VERSION );
		} catch ( Throwable $e ) {
			try {
				$this->repository->delete_by_order( $order_id );
			} catch ( Throwable $ignored ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch -- The order is unmarked, so rows left behind are never read.
			}

			$this->debug(
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
	 * Hooks the writer.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		add_action( 'tec_tickets_commerce_order_created', [ $this, 'write_created_order' ], 10, 2 );
		add_action( 'tec_tickets_commerce_order_updated', [ $this, 'sync_updated_order' ], 10, 2 );
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
			$rows[ $key ] = $this->types->get_for_item( $item )->to_row( $key, $item, $order_id, $currency ) + [ 'position' => $position++ ];
		}

		return $rows;
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
