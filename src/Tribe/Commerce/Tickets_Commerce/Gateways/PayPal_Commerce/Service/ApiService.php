<?php

namespace TEC\PaymentGateways\PayPalCommerce\Service;

/**
 * Class ApiService
 *
 * @since TBD
 *
 * @package TEC\PaymentGateways\PayPalCommerce\Service
 */
abstract class ApiService {

	/**
	 * The merchant ID for Sandbox mode.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	private $sandbox_merchant_id = '<REDACTED>';

	/**
	 * The client ID for Sandbox mode.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	private $sandbox_client_id = '<REDACTED>';

	/**
	 * The client secret for Sandbox mode.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	private $sandbox_client_secret = '<REDACTED>';

	/**
	 * The merchant ID for Live mode.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	private $live_merchant_id = '<REDACTED>';

	/**
	 * The client ID for Live mode.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	private $live_client_id = '<REDACTED>';

	/**
	 * The client secret for Live mode.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	private $live_client_secret = '<REDACTED>';

	/**
	 * The mode for PayPal. May be "live" or "sandbox".
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected $mode = 'live';

	/**
	 * Sets the mode of the request for either live mode or testing.
	 *
	 * @since TBD
	 *
	 * @param string $mode The mode to set, either "live" or "sandbox".
	 *
	 * @return self The service object.
	 */
	public function set_mode( $mode ) {
		$this->mode = $mode;

		return $this;
	}

	/**
	 * Get the merchant ID of the given mode.
	 *
	 * @since TBD
	 *
	 * @return string The merchant ID of the given mode.
	 */
	public function get_merchant_id() {
		return $this->mode === 'live' ? $this->live_merchant_id : $this->sandbox_merchant_id;
	}

	/**
	 * Get the client ID for the given mode.
	 *
	 * @since TBD
	 *
	 * @return string The client ID for the given mode.
	 */
	protected function get_client_id() {
		return $this->mode === 'live' ? $this->live_client_id : $this->sandbox_client_id;
	}

	/**
	 * Get the client secret for the given mode.
	 *
	 * @since TBD
	 *
	 * @return string The client secret for the given mode.
	 */
	protected function get_client_secret() {
		return $this->mode === 'live' ? $this->live_client_secret : $this->sandbox_client_secret;
	}

	/**
	 * Get the PayPal URL for the given mode.
	 *
	 * @param string $endpoint The endpoint path.
	 *
	 * @return string The PayPal URL for the endpoint path.
	 */
	protected function get_api_url( $endpoint ) {
		return $this->mode === 'live' ? "https://api.paypal.com/{$endpoint}" : "https://api.sandbox.paypal.com/{$endpoint}";
	}

	/**
	 * Retrieves an OAuth Token
	 *
	 * @return string|null The token or null if response was unexpected.
	 * @throws Exception
	 */
	protected function get_token() {
		$request = curl_init();

		curl_setopt_array( $request, [
			CURLOPT_URL            => $this->get_api_url( 'v1/oauth2/token' ),
			CURLOPT_HTTPHEADER     => [
				'Accept: application/json',
				'Accept-Language: en_US',
				'Content-Type: application/x-www-form-urlencoded',
			],
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_POST           => 1,
			CURLOPT_POSTFIELDS     => 'grant_type=client_credentials',
			CURLOPT_USERPWD        => "{$this->get_client_id()}:{$this->get_client_secret()}",
		] );

		$response = curl_exec( $request );

		$error_response = curl_errno( $request ) ? curl_error( $response ) : null;

		curl_close( $request );

		if ( null !== $error_response ) {
			throw new Exception( 'Error: ', $error_response );
		}

		$response = json_decode( $response );

		if ( ! $response || ! isset( $response->access_token ) ) {
			return null;
		}

		return $response->access_token;
	}
}
