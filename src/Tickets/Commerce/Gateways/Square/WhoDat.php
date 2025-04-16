<?php
/**
 * WhoDat Connection for Square.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square
 */

// phpcs:disable StellarWP.Classes.ValidClassName.NotSnakeCase
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
	protected const API_ENDPOINT = 'commerce/v1/square';

	/**
	 * The nonce action for the state.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected const STATE_NONCE_ACTION = 'tec-tc-square-connect';

	/**
	 * Get the API URL for making requests.
	 *
	 * @since TBD
	 *
	 * @param string $endpoint   The endpoint to connect to.
	 * @param array  $query_args The query arguments.
	 *
	 * @return string
	 */
	public function get_api_url( $endpoint, array $query_args = [] ): string {
		return add_query_arg( $query_args, "{$this->get_api_base_url()}/{$this->get_gateway_endpoint()}/{$endpoint}" );
	}

	/**
	 * Make a GET request to the WhoDat API.
	 *
	 * @since TBD
	 *
	 * @param string $endpoint   The endpoint to connect to.
	 * @param array  $query_args The query arguments.
	 *
	 * @return array|null
	 */
	public function get( $endpoint, array $query_args ): ?array {
		$cache           = tribe_cache();
		$cache_key       = md5( wp_json_encode( [ $endpoint, $query_args ] ) );
		$cached_response = $cache->get_transient( $cache_key );

		if ( false !== $cached_response ) {
			return $cached_response;
		}

		$url = $this->get_api_url( $endpoint, $query_args );

		$request = wp_remote_get( $url ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_remote_get_wp_remote_get

		if ( is_wp_error( $request ) ) {
			$this->log_error( 'WhoDat request error:', $request->get_error_message(), $url );

			return null;
		}

		$body = wp_remote_retrieve_body( $request );
		$body = json_decode( $body, true );

		$cache->set_transient( $cache_key, $body, HOUR_IN_SECONDS );

		return $body;
	}

	/**
	 * Make a POST request to the WhoDat API.
	 *
	 * @since TBD
	 *
	 * @param string $endpoint          The endpoint to connect to.
	 * @param array  $query_args        The query arguments.
	 * @param array  $request_arguments The request arguments.
	 *
	 * @return array|null
	 */
	public function post( $endpoint, array $query_args = [], array $request_arguments = [] ): ?array {
		$url = $this->get_api_url( $endpoint, $query_args );

		$default_arguments = [
			'body' => [],
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
			$this->log_error( 'Response:', print_r( $request, true ), '--->' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r

			return null;
		}

		return $body;
	}

	/**
	 * Log an error message for debugging purposes.
	 *
	 * @since TBD
	 *
	 * @param string $type    The type of error.
	 * @param string $message The error message.
	 * @param string $url     The URL that was being requested.
	 */
	public function log_error( $type, $message, $url ): void {
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
	public function connect_account(): string {
		$merchant = tribe( Merchant::class );

		// Generate and store the code challenge using the Merchant class.
		$code_challenge = $merchant->generate_code_challenge();
		$user_id        = get_current_user_id();

		wp_set_current_user( 0 );
		$nonce = wp_create_nonce( $this->get_state_nonce_action() );
		wp_set_current_user( $user_id );

		$query_args = [
			'mode'                  => tec_tickets_commerce_is_sandbox_mode() ? 'sandbox' : 'live',
			'code_challenge'        => $code_challenge,
			'code_challenge_method' => 'S256',
			'url'                   => tribe( On_Boarding_Endpoint::class )->get_return_url(),
			'state'                 => $nonce,
		];

		$connection_response = $this->get( 'oauth/authorize', $query_args );

		if ( empty( $connection_response['auth_url'] ) ) {
			do_action(
				'tribe_log',
				'error',
				'Failed to retrieve Square OAuth authorize URL',
				[
					'source'   => 'tickets-commerce',
					'response' => $connection_response,
				]
			);

			return '';
		}

		return $connection_response['auth_url'];
	}

	/**
	 * Get the state nonce action.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_state_nonce_action(): string {
		return self::STATE_NONCE_ACTION;
	}

	/**
	 * Get the return URL for OAuth redirects.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_return_url(): string {
		return rest_url( 'tribe/tickets/v1/commerce/square/on-boarding' );
	}

	/**
	 * De-authorize the current seller account in Square oAuth.
	 *
	 * @since TBD
	 *
	 * @return ?array
	 */
	public function disconnect_account(): ?array {
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
	 * @return ?array
	 */
	public function refresh_token(): ?array {
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
	public function get_token_status(): ?array {
		$merchant = tribe( Merchant::class );

		$query_args = [
			'access_token' => $merchant->get_access_token(),
			'mode'         => $merchant->get_mode(),
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
	public function get_merchant( string $merchant_id ): ?array {
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
	public function get_required_scopes( bool $include_descriptions = false ): ?array {
		$query_args = [
			'include_descriptions' => (bool) $include_descriptions,
		];

		$response = $this->get( 'oauth/scopes', $query_args );

		if ( empty( $response ) || ! isset( $response['scopes'] ) ) {
			do_action(
				'tribe_log',
				'error',
				'Failed to retrieve Square OAuth scopes',
				[
					'source'   => 'tickets-commerce',
					'response' => $response,
				]
			);

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
	public function verify_merchant_scopes(): array {
		$required_scopes_data = $this->get_required_scopes();

		if ( null === $required_scopes_data ) {
			return [
				'has_all_scopes' => false,
				'missing_scopes' => [],
				'reconnect_url'  => '',
				'error'          => 'Failed to retrieve required scopes',
			];
		}

		$required_scopes = $required_scopes_data['scopes'] ?? [];

		$token_status = $this->get_token_status();

		if ( empty( $token_status ) || isset( $token_status['error'] ) ) {
			return [
				'has_all_scopes' => false,
				'missing_scopes' => $required_scopes,
				'reconnect_url'  => $this->get_reconnect_url( $required_scopes ),
				'error'          => 'Could not verify current token status',
			];
		}

		$current_scopes = $token_status['scopes'] ?? [];

		$missing_scopes = array_diff( $required_scopes, $current_scopes );

		$result = [
			'has_all_scopes' => empty( $missing_scopes ),
			'missing_scopes' => $missing_scopes,
		];

		// If scopes are missing, provide a reconnect URL.
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
	protected function get_reconnect_url( array $scopes = [] ): string {
		$merchant = tribe( Merchant::class );

		$code_challenge = $merchant->generate_code_challenge();
		$user_id        = get_current_user_id();

		wp_set_current_user( 0 );
		$nonce = wp_create_nonce( $this->get_state_nonce_action() );
		wp_set_current_user( $user_id );

		$query_args = [
			'mode'                  => tec_tickets_commerce_is_sandbox_mode() ? 'sandbox' : 'live',
			'code_challenge'        => $code_challenge,
			'code_challenge_method' => 'S256',
			'url'                   => tribe( On_Boarding_Endpoint::class )->get_return_url(),
			'state'                 => $nonce,
		];

		if ( ! empty( $scopes ) ) {
			$query_args['scopes'] = implode( ',', $scopes );
		}

		$connection_response = $this->get( 'oauth/authorize', $query_args );

		return $connection_response['auth_url'] ?? '';
	}
}
