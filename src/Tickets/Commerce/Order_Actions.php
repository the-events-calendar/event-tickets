<?php
/**
 * The Order Actions controller: fires Tickets Commerce order lifecycle actions.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce
 */

namespace TEC\Tickets\Commerce;

use TEC\Tickets\Commerce\Order_Items\Controller as Order_Items_Controller;
use TEC\Tickets\Commerce\Order_Items\Writer;
use WP_Post;

/**
 * Class Order_Actions.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce
 */
final class Order_Actions {
	/**
	 * The items an order held before the write in progress, keyed by order ID.
	 *
	 * @since TBD
	 *
	 * @var array<int,mixed>
	 */
	private array $previous_items = [];

	/**
	 * Remembers the items an order held before its items meta is written.
	 *
	 * Whether `updated_post_meta` fires for an unchanged value depends on the database reporting changed rows, not
	 * matched ones, which a connection using `CLIENT_FOUND_ROWS` does not; comparing the items ourselves does not.
	 *
	 * @since TBD
	 *
	 * @param mixed  $check     The short-circuit value, returned as is.
	 * @param int    $object_id The post ID.
	 * @param string $meta_key  The meta key.
	 *
	 * @return mixed The short-circuit value.
	 */
	public function remember_previous_items( $check, $object_id, $meta_key ) {
		if ( Order::$items_meta_key === $meta_key ) {
			$this->previous_items[ $object_id ] = get_post_meta( $object_id, $meta_key, true );
		}

		return $check;
	}

	/**
	 * Fires the order updated action when an order's items meta changes.
	 *
	 * WordPress fires `updated_post_meta` only when an existing value changes: the first write of the items, on
	 * order creation, fires `added_post_meta` instead. Saving items that serialize the same fires nothing.
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

		$items    = maybe_unserialize( $meta_value );
		$previous = $this->previous_items[ $object_id ] ?? null;

		unset( $this->previous_items[ $object_id ] );

		if ( ! is_array( $items ) || maybe_serialize( $previous ) === maybe_serialize( $items ) ) {
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
	 * Unmarks an order stored in the Order Items table when its items change while that feature is off.
	 *
	 * Nothing syncs the order's rows then, so it would load its old lines once the feature is back on.
	 *
	 * @since TBD
	 *
	 * @param int $order_id The order ID.
	 *
	 * @return void
	 */
	public function unmark_order_items_version( $order_id ): void {
		if ( Order_Items_Controller::is_registered() ) {
			return;
		}

		if ( '' === get_post_meta( $order_id, Writer::VERSION_META_KEY, true ) ) {
			return;
		}

		// Updated, not deleted: a sync running elsewhere re-marks the order only if its token is still there.
		update_post_meta( $order_id, Writer::VERSION_META_KEY, '' );
		clean_post_cache( $order_id );
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
}
