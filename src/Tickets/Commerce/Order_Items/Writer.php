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
	 * The mapper that turns order items into rows.
	 *
	 * @since TBD
	 *
	 * @var Mapper
	 */
	private Mapper $mapper;

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
	 * @param Container   $container  The container.
	 * @param Mapper      $mapper     The mapper that turns order items into rows.
	 * @param Order_Items $repository The Order Items repository.
	 */
	public function __construct( Container $container, Mapper $mapper, Order_Items $repository ) {
		parent::__construct( $container );
		$this->mapper     = $mapper;
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
			$currency   = get_post_meta( $order_id, Order::$currency_meta_key, true ) ?: Currency::get_currency_code();
			$created_at = gmdate( 'Y-m-d H:i:s' );
			$rows       = [];

			foreach ( $items as $key => $item ) {
				$rows[] = $this->mapper->to_row( $key, $item, $order_id, $currency ) + [ 'created_at' => $created_at ];
			}

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
	 * Hooks the writer.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		add_action( 'tec_tickets_commerce_order_created', [ $this, 'write_created_order' ], 10, 2 );
	}
}
