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
	 * Get the Checkout page ID.
	 *
	 * @since TBD
	 *
	 *
	 * @return int|null
	 */
	public function get_page_id() {
		$checkout_page = (int) tribe_get_option( tribe( Settings::class )->option_checkout_page );

		if ( empty( $checkout_page ) ) {
			return null;
		}

		/**
		 * Allows filtering of the Page ID for the Checkout page.
		 *
		 * @since TBD
		 *
		 * @param int|null $checkout_page Which page is used in the settings.
		 */
		return apply_filters( 'tec_tickets_commerce_checkout_page_id', $checkout_page );
	}

	/**
	 * Determine the Current checkout URL.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_url() {
		$url = home_url( '/' );
		$checkout_page = $this->get_page_id();

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

	/**
	 * Determines if the current page is the Checkout page.
	 *
	 * @since TBD
	 *
	 *
	 * @return bool
	 */
	public function is_current_page() {
		if ( is_admin() ) {
			return false;
		}

		$current_page = get_queried_object_id();
		$is_current_page = $this->get_page_id() === $current_page;

		/**
		 * @todo determine hte usage of tribe_ticket_redirect_to
		 * 		$redirect = tribe_get_request_var( 'tribe_tickets_redirect_to', null );
		 */

		/**
		 * Allows modifications to the conditional of if we are in the checkout page.
		 *
		 * @since TBD
		 *
		 * @param bool $is_current_page Are we in the current page for checkout.
		 */
		return tribe_is_truthy( apply_filters( 'tec_tickets_commerce_checkout_is_current_page', $is_current_page ) );
	}

	public function parse_request() {
		if ( ! $this->is_current_page() ) {
			return;
		}

		$cookie_param = tribe_get_request_var( Cart::$cookie_query_arg, false );
		if ( $cookie_param ) {
			tribe( Cart::class )->set_cookie_invoice_number( $cookie_param );
			$items = tribe( Cart::class )->get_tickets_in_cart();
		}
		$i=1;
	}
}