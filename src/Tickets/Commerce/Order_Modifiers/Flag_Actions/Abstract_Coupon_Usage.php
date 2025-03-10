<?php
/**
 * Abstract Coupon Usage flag action.
 *
 * @since TBD
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
 * @since TBD
 */
abstract class Abstract_Coupon_Usage extends Flag_Action_Abstract {

	use Coupons;

	/**
	 * Which Post Types we check for this flag action.
	 *
	 * @since TBD
	 *
	 * @var string[]
	 */
	protected $post_types = [
		Order::POSTTYPE
	];

	/**
	 * Handles the action flag execution.
	 *
	 * @since TBD
	 *
	 * @param Status_Interface  $new_status New post status.
	 * @param ?Status_Interface $old_status Old post status.
	 * @param WP_Post           $post       Order object as a WP_Post object.
	 */
	public function handle( Status_Interface $new_status, $old_status, WP_Post $post ) {
		// If there aren't any coupons, bail.
		if ( empty( $post->coupons ) ) {
			return;
		}

		// Handle the coupon usage updates.
		foreach ( $post->coupons as $coupon ) {
			$this->handle_coupon_usage( $coupon );
		}
	}

	/**
	 * Handles the usage of a coupon.
	 *
	 * @since TBD
	 *
	 * @param array $coupon Array of coupon data.
	 */
	abstract protected function handle_coupon_usage( $coupon );
}
