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
	 * The API URL.
	 *
	 * @since 5.1.6
	 *
	 * @var string
	 */
	protected $api_url = 'https://whodat.theeventscalendar.com/commerce/v1/paypal/';

	/**
	 * Get REST API endpoint URL for requests.
	 *
	 * @since TBD
	 *
	 *
	 * @param string $endpoint   The endpoint path.
	 * @param array  $query_args Query args appended to the URL.
	 *
	 * @return string The API URL.
	 */
	public function get_api_url( $endpoint, array $query_args = [] ) {
		return add_query_arg( $query_args, "{$this->api_url}/{$endpoint}" );
	}

	/**
	 * Generate a Unique Hash for signup. It will always be 20 characters long.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	protected function get_unique_hash() {
		if ( defined( 'NONCE_KEY' ) ) {
			$nonce_key = NONCE_KEY;
		} else {
			$nonce_key = uniqid( '', true );
		}
		if ( defined( 'NONCE_SALT' ) ) {
			$nonce_salt = NONCE_SALT;
		} else {
			$nonce_salt = uniqid( '', true );
		}

		$unique = uniqid( '', true );

		return substr( str_shuffle( implode( '-', [ $nonce_key, $nonce_salt, $unique ] ) ), 0, 20 );
	}

	/**
	 * Fetch the signup link from PayPal.
	 *
	 * @since TBD
	 *
	 * @return array|string
	 */
	public function get_seller_signup_link( $return_url ) {
		$query_args = [
			'nonce'      => $this->get_unique_hash(),
			'return_url' => esc_url( $return_url ),
		];

		return $this->get( 'seller/signup', $query_args );
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

		return $this->post( 'seller/status', $query_args );
	}

	/**
	 * Send a GET request to WhoDat.
	 *
	 * @since TBD
	 *
	 * @param string $endpoint
	 * @param array  $query_args
	 *
	 * @return mixed|null
	 */
	public function get( $endpoint, array $query_args ) {
		$url = $this->get_api_url( $endpoint, $query_args );

		$request = wp_remote_get( $url );

		if ( is_wp_error( $request ) ) {
			$this->log_error( 'WhoDat request error:', $request->get_error_message(), $url );

			return null;
		}

		$body = wp_remote_retrieve_body( $request );
		$body = json_decode( $body, true );

		return $body;
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
		$url = $this->get_api_url( $endpoint, $query_args );

		$default_arguments = [
			'body' => [],
		];
		$request_arguments = array_merge_recursive( $default_arguments, $request_arguments );
		$request           = wp_remote_post( $url, $request_arguments );

		if ( is_wp_error( $request ) ) {
			$this->log_error( 'WhoDat request error:', $request->get_error_message(), $url );

			return null;
		}

		$body = wp_remote_retrieve_body( $request );
		$body = json_decode( $body, true );

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
	 * @param string $type
	 * @param string $message
	 * @param string $url
	 */
	protected function log_error( $type, $message, $url ) {
		$log = sprintf(
			'[%s] %s %s',
			$url,
			$type,
			$message
		);
		tribe( 'logger' )->log_error( $log, 'whodat-connection' );
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
			'mode'    => tribe( Merchant::class )->get_mode(),
			'request' => 'seller-status',
		];

		$args = [
			'body' => [
				'merchant_id' => $merchant_id,
				'token'       => $access_token,
			]
		];

		/**
		 * @todo Determine the if paypal-commerce of the WhoDat makes sense.
		 */
		return $this->post( 'seller/paypal-commerce', $query_args, $args );
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
			'mode'    => tribe( Merchant::class )->get_mode(),
			'request' => 'seller-credentials',
		];

		$args = [
			'body' => [
				'token' => $access_token,
			]
		];

		/**
		 * @todo Determine the if paypal-commerce of the WhoDat makes sense.
		 */
		return $this->post( 'seller/paypal-commerce', $query_args, $args );
	}

}
