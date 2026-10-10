<?php
/**
 * Writes the lines of new Tickets Commerce orders to the Order Items table.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Items
 */

namespace TEC\Tickets\Commerce\Order_Items;

use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Order_Items\Line_Items\Line_Item_Types;
use TEC\Tickets\Commerce\Order_Items\Repositories\Order_Items;
use TEC\Tickets\Commerce\Utils\Currency;
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

			return;
		}

		update_post_meta( $order_id, self::VERSION_META_KEY, self::VERSION );
		// The order model is cached until the post is modified, and a meta write does not modify it.
		clean_post_cache( $order_id );
	}

	/**
	 * Logs a message.
	 *
	 * @since TBD
	 *
	 * @param string              $level   The log level.
	 * @param string              $message The message to log.
	 * @param array<string,mixed> $context The context to log with the message.
	 *
	 * @return void
	 */
	private function log( string $level, string $message, array $context ): void {
		do_action( 'tribe_log', $level, $message, [ 'controller' => Controller::class ] + $context );
	}
}
