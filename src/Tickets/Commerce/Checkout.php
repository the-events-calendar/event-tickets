<?php

namespace TEC\Tickets\Commerce;

/**
 * Class Checkout
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce
 */
class Checkout {

	/**
	 * Determine the Current checkout URL.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_url() {
		$url = home_url( '/' );

		/**
		 * Allows modifications to the checkout url for Tickets Commerce.
		 *
		 * @since TBD
		 *
		 * @param string $url URL for the cart.
		 */
		return (string) apply_filters( 'tec_tickets_commerce_checkout_url', $url );
	}
}