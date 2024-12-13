<?php
/**
 * Coupons repository.
 *
 * @since 5.18.0
 */

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Order_Modifiers\Repositories;

/**
 * Class Coupons
 *
 * @since 5.18.0
 */
class Coupons extends Order_Modifiers {

	/**
	 * Coupons constructor.
	 *
	 * @since 5.18.0
	 */
	public function __construct() {
		parent::__construct( 'coupon' );
	}
}
