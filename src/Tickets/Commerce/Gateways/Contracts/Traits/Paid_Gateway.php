<?php
/**
 * Tickets Commerce: Paid Gateway Trait.
 *
 * @since 5.10.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Contracts\Traits
 */

namespace TEC\Tickets\Commerce\Gateways\Contracts\Traits;

use TEC\Tickets\Commerce\Cart;

trait Paid_Gateway {
	/**
	 * @inheritDoc
	 */
	public static function should_show() {
		if ( is_admin() ) {
			return true;
		}

		$cart_total = tribe( Cart::class )->get_cart_total();
		return $cart_total > 0;
	}
}
