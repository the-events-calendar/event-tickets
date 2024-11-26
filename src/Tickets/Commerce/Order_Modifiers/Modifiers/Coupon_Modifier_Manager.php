<?php
/**
 * Coupon_Modifier_Manager class
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\Order_Modifiers\Modifiers
 */

namespace TEC\Tickets\Commerce\Order_Modifiers\Modifiers;

use TEC\Tickets\Commerce\Order_Modifiers\Modifiers\Coupon;

/**
 * Coupon_Modifier_Manager class
 *
 * @since TBD
 */
class Coupon_Modifier_Manager extends Modifier_Manager {

	/**
	 * Coupon_Modifier_Manager constructor.
	 *
	 * @param Coupon $coupon The coupon object.
	 */
	public function __construct( Coupon $coupon ) {
		$this->strategy = $coupon;
	}
}
