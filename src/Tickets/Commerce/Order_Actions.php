<?php
/**
 * The Order Actions controller: fires Tickets Commerce order lifecycle actions.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce
 */

namespace TEC\Tickets\Commerce;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;

/**
 * Class Order_Actions.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce
 */
class Order_Actions extends Controller_Contract {
	/**
	 * Unhooks the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'updated_post_meta', [ $this, 'fire_order_updated' ] );
	}

	/**
	 * Fires the order updated action when an order's items meta changes.
	 *
	 * WordPress fires `updated_post_meta` only when an existing value changes: the first write of the items, on
	 * order creation, fires `added_post_meta` instead, and saving the same items again fires nothing.
	 *
	 * @since TBD
	 *
	 * @param int    $meta_id    The meta ID.
	 * @param int    $object_id  The post ID.
	 * @param string $meta_key   The meta key.
	 * @param mixed  $meta_value The new meta value.
	 *
	 * @return void
	 */
	public function fire_order_updated( $meta_id, $object_id, $meta_key, $meta_value ): void {
		if ( Order::$items_meta_key !== $meta_key || Order::POSTTYPE !== get_post_type( $object_id ) ) {
			return;
		}

		$items = maybe_unserialize( $meta_value );

		if ( ! is_array( $items ) ) {
			return;
		}

		/**
		 * Fires after the items of an existing Tickets Commerce order changed.
		 *
		 * @since TBD
		 *
		 * @param int   $order_id The order ID.
		 * @param array $items    The order's new items, as saved to the order meta.
		 */
		do_action( 'tec_tickets_commerce_order_updated', absint( $object_id ), $items );
	}

	/**
	 * Hooks the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		add_action( 'updated_post_meta', [ $this, 'fire_order_updated' ], 10, 4 );
	}
}
