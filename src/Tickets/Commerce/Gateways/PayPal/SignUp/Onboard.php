<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal\SignUp;

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
	const TICKETS_COMMERCE_MICROSERVICE_ROUTE = 'https://whodat.theeventscalendar.com/commerce/v1/paypal/seller/';

	/**
	 * Request the signup link that redirects the seller to PayPal.
	 *
	 * @since TBD
	 *
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
	 * Fetch the signup link from PayPal.
	 *
	 * @since TBD
	 *
	 * @return array|WP_Error
	 */
	public function request_signup_link() {
		if ( ! is_admin() || ! isset( $_GET['tab'] ) || 'payments' !== $_GET['tab'] ) {
			return;
		}

		$url = add_query_arg( [
			'nonce'        => str_shuffle( uniqid( '', true ) . uniqid( '', true ) ),
			'return_url'   => esc_url( $this->get_return_url() ),
			'country_code' => 'US',
		], self::TICKETS_COMMERCE_MICROSERVICE_ROUTE . 'signup' );

		return wp_remote_get( $url );
	}

	/**
	 * When the seller completes the sign-up flow, they are redirected to this return URL on their site.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_return_url() {
		return add_query_arg( [
			'wp_nonce' => wp_create_nonce( self::PAYPAL_SIGNUP_NONCE ),
		], rest_url() . 'tickets-commerce/paypal/on-boarding/' );
	}

	/**
	 * Verify if the seller was successfully onboarded.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_seller_status() {
		$saved_merchant_id = get_option( 'tickets_commerce_merchant_id_in_paypal' );

		if ( false === $saved_merchant_id ) {
			return 'inactive';
		}

		$url     = add_query_arg( [
			'merchant_id' => $saved_merchant_id,
		], self::TICKETS_COMMERCE_MICROSERVICE_ROUTE . 'status' );
		$request = wp_remote_post( $url );
		$body    = json_decode( wp_remote_retrieve_body( $request ) );

		if ( ! isset( $body->payments_receivable ) || ! isset( $body->products[0]->name ) || ! isset( $body->products[0]->status ) ) {
			return 'inactive';
		}

		if ( true === $body->payments_receivable && 'EXPRESS_CHECKOUT' === $body->products[0]->name && 'ACTIVE' === $body->products[0]->status ) {
			return 'active';
		}

		return 'inactive';
	}


}