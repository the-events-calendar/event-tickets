<?php

namespace TEC\Tickets\Commerce\Gateways\Square;

use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_WhoDat;
use TEC\Tickets\Commerce\Gateways\Square\REST\On_Boarding_Endpoint;
use Tribe__Utils__Array as Arr;

/**
 * Class WhoDat. Handles connection to Square when the platform keys are needed.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square
 */
class WhoDat extends Abstract_WhoDat {

	/**
	 * The API Path.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected string $api_endpoint = 'square';

	/**
	 * The nonce action for the state.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected string $state_nonce_action = 'tec-tc-square-connect';

	/**
	 * Get the API URL for making requests.
	 *
	 * @since 5.3.0
	 *
	 * @param string $endpoint   The endpoint to connect to.
	 * @param array  $query_args The query arguments.
	 *
	 * @return string
	 */
	public function get_api_url( $endpoint, array $query_args = [] ) {
		return add_query_arg( $query_args, "{$this->get_api_base_url()}/{$this->get_gateway_endpoint()}/{$endpoint}" );
	}

	/**
	 * Make a GET request to the WhoDat API.
	 *
	 * @since 5.3.0
	 *
	 * @param string $endpoint   The endpoint to connect to.
	 * @param array  $query_args The query arguments.
	 *
	 * @return array|null
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
	 * Make a POST request to the WhoDat API.
	 *
	 * @since 5.3.0
	 *
	 * @param string $endpoint          The endpoint to connect to.
	 * @param array  $query_args        The query arguments.
	 * @param array  $request_arguments The request arguments.
	 *
	 * @return array|null
	 */
	public function post( $endpoint, array $query_args = [], array $request_arguments = [] ) {
		$url = $this->get_api_url( $endpoint, $query_args );

		$default_arguments = [
			'body'      => [],
			'sslverify' => false,
		];

		foreach ( $default_arguments as $key => $default_argument ) {
			$request_arguments[ $key ] = array_merge( $default_argument, Arr::get( $request_arguments, $key, [] ) );
		}
		$request_arguments = array_filter( $request_arguments );
		$request           = wp_remote_post( $url, $request_arguments );

		if ( is_wp_error( $request ) ) {
			$this->log_error( 'WhoDat request error:', $request->get_error_message(), $url );

			return null;
		}

		$body = wp_remote_retrieve_body( $request );
		$body = json_decode( $body, true );

		if ( ! is_array( $body ) ) {
			$this->log_error( 'WhoDat unexpected response:', $body, $url );
			$this->log_error( 'Response:', print_r( $request, true ), '--->' );

			return null;
		}

		return $body;
	}

	/**
	 * Log an error message for debugging purposes.
	 *
	 * @since 5.3.0
	 *
	 * @param string $type    The type of error.
	 * @param string $message The error message.
	 * @param string $url     The URL that was being requested.
	 */
	public function log_error( $type, $message, $url ) {
		$log = sprintf(
			'[%s] %s %s',
			$url,
			$type,
			$message
		);
		tribe( 'logger' )->log_error( $log, 'whodat-connection' );
	}

	/**
	 * Creates a new account link for the client and redirects the user to setup the account details.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function connect_account() {
		$merchant = tribe( Merchant::class );

		// Generate and store the code challenge using the Merchant class
		$code_challenge = $merchant->generate_code_challenge();
		$user_id = get_current_user_id();

		wp_set_current_user( 0 );
		$nonce = wp_create_nonce( $this->state_nonce_action );
		wp_set_current_user( $user_id );

		$query_args = [
			'mode'                  => tec_tickets_commerce_is_sandbox_mode() ? 'sandbox' : 'live',
			'code_challenge'        => $code_challenge,
			'code_challenge_method' => 'S256',
			'url'                   => tribe( On_Boarding_Endpoint::class )->get_return_url(),
			'state'                 => $nonce,
		];

		$connection_response = $this->get( 'oauth/authorize', $query_args );

		return $connection_response['auth_url'];
	}

	public function get_state_nonce_action(): string {
		return $this->state_nonce_action;
	}

	/**
	 * Get the return URL for OAuth redirects.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_return_url() {
		return rest_url( 'tribe/tickets/v1/commerce/square/on-boarding' );
	}

	/**
	 * De-authorize the current seller account in Square oAuth.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function disconnect_account() {
		$account_id = tribe( Merchant::class )->get_account_id();

		$query_args = [
			'merchant_id' => $account_id,
			'return_url'  => esc_url( $this->get_return_url() ),
		];

		return $this->get( 'token/revoke', $query_args );
	}

	/**
	 * Register a newly connected Square account to the website.
	 *
	 * @since TBD
	 *
	 * @param array $account_data array of data returned from Square after a successful connection.
	 *
	 * @return array|null
	 */
	public function onboard_account( $account_data ) {
		$merchant = tribe( Merchant::class );

		// Get the stored code verifier for PKCE
		$code_verifier = $merchant->get_code_verifier();

		if ( empty( $code_verifier ) ) {
			$this->log_error( 'OAuth Error', 'Missing code_verifier during token exchange', '' );
			return null;
		}

		$query_args = [
			'grant_type'    => 'authorization_code',
			'code'          => $account_data['code'],
			'code_verifier' => $code_verifier,
		];

		// Delete the code verifier as it's no longer needed
		$merchant->delete_code_verifier();

		return $this->get( 'authorize/redirect', $query_args );
	}

	/**
	 * Requests WhoDat to refresh the oAuth tokens.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function refresh_token() {
		$refresh_token = tribe( Merchant::class )->get_refresh_token();

		$query_args = [
			'grant_type'    => 'refresh_token',
			'refresh_token' => $refresh_token,
		];

		return $this->get( 'token/refresh', $query_args );
	}

	/**
	 * Get the token status from Square.
	 *
	 * @since TBD
	 *
	 * @return array|null
	 */
	public function get_token_status() {
		$merchant_id = tribe( Merchant::class )->get_merchant_id();

		$query_args = [
			'merchant_id' => $merchant_id,
		];

		return $this->get( 'token/status', $query_args );
	}

	/**
	 * Get merchant information from Square.
	 *
	* @since TBD
	 *
	 * @param string $merchant_id The merchant ID.
	 *
	 * @return array|null
	 */
	public function get_merchant( $merchant_id ) {
		$query_args = [
			'merchant_id' => $merchant_id,
		];

		return $this->get( 'merchants/' . $merchant_id, $query_args );
	}
}
