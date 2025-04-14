<?php
/**
 * Abstract Coupon Usage flag action.
 *
 * @since 5.21.0
 */

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Order_Modifiers\Flag_Actions;

use TEC\Tickets\Commerce\Flag_Actions\Flag_Action_Abstract;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Order_Modifiers\Traits\Coupons;
use TEC\Tickets\Commerce\Status\Status_Interface;
use WP_Post;

/**
 * Class Abstract_Coupon_Usage
 *
 * @since 5.21.0
 */
abstract class Abstract_Coupon_Usage extends Flag_Action_Abstract {

	use Coupons;

	/**
	 * Meta key for the calculated usage.
	 *
	 * @since 5.21.0
	 *
	 * @var string
	 */
	protected string $meta_key = '_tec_tickets_coupon_usage_calculated';

	/**
	 * Which Post Types we check for this flag action.
	 *
	 * @since 5.21.0
	 *
	 * @var string[]
	 */
	protected $post_types = [
		Order::POSTTYPE,
	];

	/**
	 * Handles the action flag execution.
	 *
	 * @since 5.21.0
	 *
	 * @param Status_Interface  $new_status New post status.
	 * @param ?Status_Interface $old_status Old post status.
	 * @param WP_Post           $post       Order object as a WP_Post object.
	 */
	public function handle( Status_Interface $new_status, $old_status, WP_Post $post ) {
		// If there aren't any coupons, don't handle.
		if ( empty( $post->coupons ) ) {
			return;
		}

		foreach ( $post->coupons as $coupon ) {
			$this->handle_coupon_usage( $coupon, $post );
		}
	}

	/**
	 * Determine if the usage has been calculated.
	 *
	 * @param WP_Post $post The order post object.
	 *
	 * @return bool True if the usage has been calculated, false otherwise.
	 */
	protected function has_usage_been_calculated( $post ): bool {
		$usage = get_post_meta( $post->ID, $this->meta_key, true );

		return ! empty( $usage );
	}

	/**
	 * Marks the usage as calculated.
	 *
	 * @param WP_Post $order The order post object.
	 *
	 * @return void
	 */
	protected function mark_usage_calculated( $order ) {
		update_post_meta( $order->ID, $this->meta_key, 'calculated' );
	}

	/**
	 * Marks the usage as uncalculated.
	 *
	 * @param WP_Post $order The order post object.
	 *
	 * @return void
	 */
	protected function mark_usage_uncalculated( $order ) {
		delete_post_meta( $order->ID, $this->meta_key );
	}

	/**
	 * Handles the usage of a coupon.
	 *
	 * @since 5.21.0
	 *
	 * @param array   $coupon Array of coupon data.
	 * @param WP_Post $order  Order object as a WP_Post object.
	 */
	abstract protected function handle_coupon_usage( array $coupon, $order );
}
