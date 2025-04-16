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

		return $this->get( 'oauth/token/revoke', $query_args );
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

		return $this->get( 'oauth/token/refresh', $query_args );
	}

	/**
	 * Get the token status from Square.
	 *
	 * @since TBD
	 *
	 * @return array|null
	 */
	public function get_token_status() {
		$merchant = tribe( Merchant::class );

		$query_args = [
			'access_token' => $merchant->get_access_token(),
			'mode' => $merchant->get_mode(),
		];

		return $this->get( 'oauth/token/status', $query_args );
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

	/**
	 * Get required OAuth scopes for the Square integration.
	 *
	 * @since TBD
	 *
	 * @param bool $include_descriptions Whether to include detailed descriptions for each scope.
	 *
	 * @return array|null Array of required scopes or null if the request fails.
	 */
	public function get_required_scopes( $include_descriptions = false ) {
		$query_args = [
			'include_descriptions' => (bool) $include_descriptions,
		];

		$response = $this->get( 'oauth/scopes', $query_args );

		if ( empty( $response ) || ! isset( $response['scopes'] ) ) {
			do_action( 'tribe_log', 'error', 'Failed to retrieve Square OAuth scopes', [
				'source' => 'tickets-commerce',
				'response' => $response,
			] );
			return null;
		}

		return $response;
	}

	/**
	 * Verify if the currently connected merchant has all required scopes.
	 *
	 * @since TBD
	 *
	 * @return array {
	 *     Array containing verification results
	 *
	 *     @type bool   $has_all_scopes    Whether the merchant has all required scopes.
	 *     @type array  $missing_scopes    Array of missing scope IDs, if any.
	 *     @type string $reconnect_url     URL to reconnect with the correct scopes, if needed.
	 * }
	 */
	public function verify_merchant_scopes() {
		// Get required scopes
		$required_scopes_data = $this->get_required_scopes();

		if ( null === $required_scopes_data ) {
			return [
				'has_all_scopes' => false,
				'missing_scopes' => [],
				'reconnect_url' => '',
				'error' => 'Failed to retrieve required scopes',
			];
		}

		$required_scopes = isset( $required_scopes_data['scopes'] )
			? $required_scopes_data['scopes']
			: [];

		// Get token status which includes the scopes
		$token_status = $this->get_token_status();

		if ( empty( $token_status ) || isset( $token_status['error'] ) ) {
			return [
				'has_all_scopes' => false,
				'missing_scopes' => $required_scopes,
				'reconnect_url' => $this->get_reconnect_url( $required_scopes ),
				'error' => 'Could not verify current token status',
			];
		}

		// Extract current scopes
		$current_scopes = isset( $token_status['scopes'] )
			? $token_status['scopes']
			: [];

		// Find missing scopes
		$missing_scopes = array_diff( $required_scopes, $current_scopes );

		$result = [
			'has_all_scopes' => empty( $missing_scopes ),
			'missing_scopes' => $missing_scopes,
		];

		// If scopes are missing, provide a reconnect URL
		if ( ! empty( $missing_scopes ) ) {
			$result['reconnect_url'] = $this->get_reconnect_url( $required_scopes );
		}

		return $result;
	}

	/**
	 * Get a URL to reconnect with specific scopes.
	 *
	 * @since TBD
	 *
	 * @param array $scopes Array of required scope IDs.
	 *
	 * @return string Reconnect URL.
	 */
	protected function get_reconnect_url( array $scopes = [] ) {
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

		// Add scopes if provided
		if ( ! empty( $scopes ) ) {
			$query_args['scopes'] = implode( ',', $scopes );
		}

		$connection_response = $this->get( 'oauth/authorize', $query_args );

		return isset( $connection_response['auth_url'] ) ? $connection_response['auth_url'] : '';
	}
}
