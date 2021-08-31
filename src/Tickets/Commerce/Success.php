<?php

namespace TEC\Tickets\Commerce;

/**
 * Class Success
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce
 */
class Success {
	/**
	 * Param we use to store the order ID.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $order_id_query_arg = 'tc-order-id';

	/**
	 * Get the Success page ID.
	 *
	 * @since TBD
	 *
	 *
	 * @return int|null
	 */
	public function get_page_id() {
		$success_page = (int) tribe_get_option( Settings::$option_success_page );

		if ( empty( $success_page ) ) {
			return null;
		}

		/**
		 * Allows filtering of the Page ID for the Success page.
		 *
		 * @since TBD
		 *
		 * @param int|null $success_page Which page is used in the settings.
		 */
		return apply_filters( 'tec_tickets_commerce_success_page_id', $success_page );
	}

	/**
	 * Determine the Current success URL.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_url() {
		$url = home_url( '/' );
		$success_page = $this->get_page_id();

		if ( is_numeric( $success_page ) ) {
			$success_page = get_post( $success_page );
		}

		// Only modify the URL in case we have a success page setup in the settings.
		if ( $success_page instanceof \WP_Post ) {
			$url = get_the_permalink( $success_page );
		}

		/**
		 * Allows modifications to the success url for Tickets Commerce.
		 *
		 * @since TBD
		 *
		 * @param string $url URL for the cart.
		 */
		return (string) apply_filters( 'tec_tickets_commerce_success_url', $url );
	}

	/**
	 * Determines if the current page is the success page.
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
		 * Allows modifications to the conditional of if we are in the success page.
		 *
		 * @since TBD
		 *
		 * @param bool $is_current_page Are we in the current page for checkout.
		 */
		return tribe_is_truthy( apply_filters( 'tec_tickets_commerce_success_is_current_page', $is_current_page ) );
	}

	/**
	 * If there is any data or request management or parsing that needs to happen on the success page here is where
	 * we do it.
	 *
	 * @since TBD
	 */
	public function parse_request() {
		if ( ! $this->is_current_page() ) {
			return;
		}

		// In case the ID is passed we set the cookie for usage.
		$cookie_param = tribe_get_request_var( Cart::$cookie_query_arg, false );
		if ( $cookie_param ) {
			tribe( Cart::class )->set_cart_hash_cookie( $cookie_param );
		}
	}
}