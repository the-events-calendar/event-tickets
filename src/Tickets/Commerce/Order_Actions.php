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
use WP_Post;

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
		remove_action( 'deleted_post', [ $this, 'fire_order_deleted' ] );
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
	 * Fires the order deleted action when an order is permanently deleted.
	 *
	 * Trashing an order does not delete it; emptying the trash does. Hooked after the post row is gone, so listeners
	 * never act on an order whose deletion then fails.
	 *
	 * @since TBD
	 *
	 * @param int     $post_id The post ID.
	 * @param WP_Post $post    The deleted post.
	 *
	 * @return void
	 */
	public function fire_order_deleted( $post_id, $post ): void {
		if ( ! $post instanceof WP_Post || Order::POSTTYPE !== $post->post_type ) {
			return;
		}

		/**
		 * Fires after a Tickets Commerce order has been permanently deleted.
		 *
		 * @since TBD
		 *
		 * @param int $order_id The order ID.
		 */
		do_action( 'tec_tickets_commerce_order_deleted', absint( $post_id ) );
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
		add_action( 'deleted_post', [ $this, 'fire_order_deleted' ], 10, 2 );
	}
}
