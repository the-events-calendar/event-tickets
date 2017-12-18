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
	 * @param int   $post_id The ID of the post tickets were purchased from.
	 *
	 * @return string
	 */
	public function success_url( $order = '', $post_id = null ) {
		$success_page_id = tribe_get_option( 'ticket-paypal-success-page' );
		if ( ! empty( $success_page_id ) && is_page( $success_page_id ) ) {
			$url = add_query_arg( array( 'p' => $success_page_id, 'tribe-tpp-order' => $order ), home_url() );
		} else {
			// use the post single page
			$url = add_query_arg( array( 'tribe-tpp-order' => $order ), get_permalink( $post_id ) );
		}

		return $url;
	}
}
