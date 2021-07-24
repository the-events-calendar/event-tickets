<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal;

use WP_Error;

/**
 * Class Onboard
 *
 * @package TEC\Tickets\Commerce\Gateways\PayPal
 */
class Onboard {

	/**
	 * The PayPal SignUp nonce.
	 */
	const PAYPAL_SIGNUP_NONCE = 'tickets_commerce_paypal_signup';

	/**
	 * The endpoint for fetching a new partner onboard link.
	 */
	const PAYPAL_SIGNUP_ENDPOINT = 'https://whodat.theeventscalendar.com/commerce/v1/paypal/seller/signup';

	/**
	 * @return false
	 */
	public function get_paypal_signup_link() {
		$request = $this->request_signup_link();

		if ( is_wp_error( $request ) ) {
			return false;
		}

		$request_body = json_decode( wp_remote_retrieve_body( $request ) );

		if ( empty( $request_body ) || ! isset( $request_body->links[1]->href ) ) {
			return false;
		}

		return $request_body->links[1]->href;
	}

	/**
	 * @return string
	 */
	public function get_return_url() {
		$url = add_query_arg( [
			'wp_nonce' => wp_create_nonce( self::PAYPAL_SIGNUP_NONCE ),
		], admin_url() );

		return esc_url( $url );
	}

	/**
	 * @return array|WP_Error
	 */
	public function request_signup_link() {
		$url = add_query_arg( [
			'nonce'        => str_shuffle( uniqid( '', true ) . uniqid( '', true ) ),
			'return_url'   => esc_url( $this->get_return_url() ),
			'country_code' => 'US',
		], self::PAYPAL_SIGNUP_ENDPOINT );

		return wp_remote_get( $url );
	}
}