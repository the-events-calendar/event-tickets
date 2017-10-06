<?php

/**
 * Class Tribe__Tickets__Commerce__PayPal__Endpoints
 *
 * @since TBD
 */
class Tribe__Tickets__Commerce__PayPal__Endpoints {

	/**
	 * Returns the full URL to the success endpoint.
	 *
	 * @since TBD
	 *
	 * @param string $order The order alphanumeric string.
	 *
	 * @return string
	 */
	public function success_url( $order = '' ) {
		$success_page_id = tribe_get_option( 'ticket-paypal-success-page' );
		$url             = add_query_arg( array( 'p' => $success_page_id, 'tribe-tpp-order' => $order ), home_url() );

		return $url;
	}
}
