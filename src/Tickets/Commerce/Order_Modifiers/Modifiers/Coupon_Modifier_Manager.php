<?php
/**
 * Coupon_Modifier_Manager class
 *
 * @since 5.18.0
 *
 * @package TEC\Tickets\Commerce\Order_Modifiers\Modifiers
 */

namespace TEC\Tickets\Commerce\Order_Modifiers\Modifiers;

/**
 * Coupon_Modifier_Manager class
 *
 * @since 5.18.0
 */
class Coupon_Modifier_Manager extends Modifier_Manager {

	/**
	 * Coupon_Modifier_Manager constructor.
	 *
	 * @param Coupon $coupon The coupon object.
	 */
	public function __construct( Coupon $coupon ) {
		parent::__construct( $coupon );
	}
}
