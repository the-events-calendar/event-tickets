<?php
/**
 * Coupons repository.
 *
 * @since TBD
 */

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Order_Modifiers\Repositories;

/**
 * Class Coupons
 *
 * @since TBD
 */
class Coupons extends Order_Modifiers {

	/**
	 * Coupons constructor.
	 *
	 * @since TBD
	 */
	public function __construct() {
		parent::__construct( 'coupon' );
	}
}
