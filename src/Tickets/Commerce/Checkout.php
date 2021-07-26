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

		$checkout_page = tribe_get_option( tribe( Settings::class )->option_checkout_page );

		if ( is_numeric( $checkout_page ) ) {
			$checkout_page = get_post( $checkout_page );
		}

		// Only modify the URL in case we have a checkout page setup in the settings.
		if ( $checkout_page instanceof \WP_Post ) {
			$url = get_the_permalink( $checkout_page );
		}

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