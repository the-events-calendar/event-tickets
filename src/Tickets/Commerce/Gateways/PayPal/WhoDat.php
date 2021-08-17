<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal;

use TEC\Tickets\Commerce\Gateways\PayPal\REST\On_Boarding;
use Tribe__Utils__Array as Arr;

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
	protected $api_url = 'https://whodat.theeventscalendar.com/commerce/v1/paypal';

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
	 * Fetch the signup link from PayPal.
	 *
	 * @since TBD
	 *
	 * @return array|string
	 */
	public function get_seller_signup_data( $hash ) {
		if ( empty( $hash ) ) {
			$hash = tribe( Signup::class )->generate_unique_signup_hash();
		}

		$return_url = tribe( On_Boarding::class )->get_return_url( $hash );
		$query_args = [
			'mode'        => tribe( Merchant::class )->get_mode(),
			'nonce'       => $hash,
			'tracking_id' => urlencode( tribe( Signup::class )->generate_unique_tracking_id() ),
			'return_url'  => esc_url( $return_url ),
		];

		return $this->get( 'seller/signup', $query_args );
	}

	/**
	 * Fetch the seller referral Data from WhoDat/PayPal.
	 *
	 * @since TBD
	 *
	 * @param string $url Which URL WhoDat needs to request.
	 *
	 * @return array
	 */
	public function get_seller_referral_data( $url ) {
		$query_args = [
			'mode' => tribe( Merchant::class )->get_mode(),
			'url'  => $url
		];

		return $this->get( 'seller/referral-data', $query_args );
	}

	/**
	 * Verify if the seller was successfully onboarded.
	 *
	 * @since TBD
	 *
	 * @param string $saved_merchant_id The ID we are looking at Paypal with.
	 *
	 * @return array
	 */
	public function get_seller_status( $saved_merchant_id ) {
		$query_args = [
			'mode'        => tribe( Merchant::class )->get_mode(),
			'merchant_id' => $saved_merchant_id
		];

		return $this->post( 'seller/status', $query_args );
	}

	/**
	 * Get seller rest API credentials
	 *
	 * @since TBD
	 *
	 * @param string $access_token
	 *
	 * @return array|null
	 */
	public function get_seller_credentials( $access_token ) {
		$query_args = [
			'mode'         => tribe( Merchant::class )->get_mode(),
			'access_token' => $access_token,
		];

		return $this->post( 'seller/credentials', $query_args );
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
}
