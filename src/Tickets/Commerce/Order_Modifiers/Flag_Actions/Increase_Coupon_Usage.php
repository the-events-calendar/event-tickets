<?php
/**
 * Increase Coupon Usage flag action.
 *
 * @since TBD
 */

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Order_Modifiers\Flag_Actions;

use WP_Post;

/**
 * Class Increase_Coupon_Usage
 *
 * @since TBD
 */
class Increase_Coupon_Usage extends Abstract_Coupon_Usage {

	/**
	 * Which flags are associated and will trigger this action.
	 *
	 * @since TBD
	 *
	 * @var string[]
	 */
	protected $flags = [
		'decrease_stock',
	];

	/**
	 * Handles the usage of a coupon.
	 *
	 * @since TBD
	 *
	 * @param array   $coupon Array of coupon data.
	 * @param WP_Post $order  Order object as a WP_Post object.
	 */
	protected function handle_coupon_usage( $coupon, $order ) {
		$this->add_coupon_use( $coupon['id'], $coupon['quantity'] );
	}
}
