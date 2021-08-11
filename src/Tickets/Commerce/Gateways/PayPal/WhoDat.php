<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal;

/**
 * Class Connect_Client
 *
 * @since   5.1.6
 *
 * @package TEC\Tickets\Commerce\Gateways\PayPal
 */
class WhoDat {

	/**
	 * The endpoint for fetching a new partner onboard link.
	 */
	const TICKETS_COMMERCE_MICROSERVICE_ROUTE = 'https://whodat.theeventscalendar.com/commerce/v1/paypal/seller/';

	/**
	 * Fetch the signup link from PayPal.
	 *
	 * @since TBD
	 *
	 * @return array|string
	 */
	public function get_seller_signup_link( $return_url ) {
		$query_args = [
			'nonce'        => str_shuffle( uniqid( '', true ) . uniqid( '', true ) ),
			'return_url'   => esc_url( $return_url ),
		];

		return $this->get( 'signup', $query_args );
	}

	/**
	 * Verify if the seller was successfully onboarded.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public function get_seller_status( $saved_merchant_id ) {
		$query_args = [ 'merchant_id' => $saved_merchant_id ];

		return $this->post('status', $query_args );
	}

	/**
	 * Send a GET request to WhoDat.
	 *
	 * @since TBD
	 *
	 * @param  string  $endpoint
	 * @param  array  $query_args
	 *
	 * @return mixed|null
	 */
	public function get( $endpoint, array $query_args ) {
		$url = add_query_arg( $query_args, self::TICKETS_COMMERCE_MICROSERVICE_ROUTE . $endpoint );

		$request = wp_remote_get( $url );

		if ( is_wp_error( $request ) ) {
			$this->log_error( 'WhoDat request error:', $request->get_error_message(), $url );

			return null;
		}

		return json_decode( wp_remote_retrieve_body( $request ) );
	}

	/**
	 * Send a POST request to WhoDat.
	 *
	 * @since TBD
	 *
	 * @param string $endpoint
	 * @param array  $query_args
	 * @param array  $request_arguments
	 *
	 * @return array|null
	 */
	public function post( $endpoint, array $query_args = [], array $request_arguments = [] ) {
		$url = add_query_arg( $query_args, self::TICKETS_COMMERCE_MICROSERVICE_ROUTE . $endpoint );

		$default_arguments = [
			'body' => [],
		];
		$request_arguments = array_merge_recursive( $default_arguments, $request_arguments );
		$request = wp_remote_post( $url, $request_arguments );

		if ( is_wp_error( $request ) ) {
			$this->log_error( 'WhoDat request error:', $request->get_error_message(), $url );

			return null;
		}

		$body = json_decode( wp_remote_retrieve_body( $request ), true );

		if ( ! is_array( $body ) ) {
			$this->log_error( 'WhoDat unexpected response:', $body, $url );

			return null;
		}

		return $body;
	}

	/**
	 * Log WhoDat errors.
	 *
	 * @since TBD
	 *
	 * @param string $error_type
	 * @param string $error_message
	 * @param string $url
	 */
	public function log_error( $error_type, $error_message, $url ) {
		tribe( 'logger' )->log_error( sprintf(
			'[%s] '. $error_type .' %s',
			$url,
			$error_message
		), 'whodat-connection' );
	}

	/**
	 * Get seller on-boarding details from seller.
	 *
	 * @since 5.1.6
	 *
	 * @param string $access_token
	 * @param string $merchant_id
	 *
	 * @return array|null
	 */
	public function get_seller_on_boarding_details_from_paypal( $merchant_id, $access_token ) {
		$query_args = [
			'mode' => tribe( Merchant::class )->get_mode(),
			'request' => 'seller-status',
		];

		$args = [
			'body' => [
				'merchant_id' => $merchant_id,
				'token' => $access_token,
			]
		];

		return $this->post( 'paypal-commerce', $query_args, $args );
	}

	/**
	 * Get seller rest API credentials
	 *
	 * @since 5.1.6
	 *
	 * @param string $access_token
	 *
	 * @return array|null
	 */
	public function get_seller_rest_api_credentials( $access_token ) {
		$query_args = [
			'mode' => tribe( Merchant::class )->get_mode(),
			'request' => 'seller-credentials',
		];

		$args = [
			'body' => [
				'token' => $access_token,
			]
		];

		return $this->post( 'paypal-commerce', $query_args, $args );
	}

}
