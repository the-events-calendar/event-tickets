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
	 * The items an order held before the write in progress, keyed by order ID.
	 *
	 * @since TBD
	 *
	 * @var array<int,mixed>
	 */
	private array $previous_items = [];

	/**
	 * Unhooks the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_filter( 'update_post_metadata', [ $this, 'remember_previous_items' ] );
		remove_action( 'added_post_meta', [ $this, 'fire_order_updated' ] );
		remove_action( 'updated_post_meta', [ $this, 'fire_order_updated' ] );
		remove_action( 'deleted_post', [ $this, 'fire_order_deleted' ] );
	}

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
	 * Fires the order updated action when an order's items meta is added or changes.
	 *
	 * WordPress fires `updated_post_meta` only when an existing value changes and `added_post_meta` for the first
	 * write of the items, so both are hooked. This signals the items meta only, not a created order. Saving items
	 * that serialize the same fires nothing.
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
		 * Fires after the items meta of a Tickets Commerce order was added or changed.
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
		add_filter( 'update_post_metadata', [ $this, 'remember_previous_items' ], 10, 3 );
		add_action( 'added_post_meta', [ $this, 'fire_order_updated' ], 10, 4 );
		add_action( 'updated_post_meta', [ $this, 'fire_order_updated' ], 10, 4 );
		add_action( 'deleted_post', [ $this, 'fire_order_deleted' ], 10, 2 );
	}
}
