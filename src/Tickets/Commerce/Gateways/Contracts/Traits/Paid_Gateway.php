<?php

namespace TEC\Tickets\Commerce\Gateways\Contracts\Traits;

use TEC\Tickets\Commerce\Cart;

trait Paid_Gateway {
	/**
	 * @inheritDoc
	 */
	public static function should_show() {
		$cart_total = tribe( Cart::class )->get_cart_total();
		return $cart_total > 0;
	}
}