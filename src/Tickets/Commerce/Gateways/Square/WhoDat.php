<?php
/**
 * WhoDat Connection for Square.
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square
 */

// phpcs:disable StellarWP.Classes.ValidClassName.NotSnakeCase
namespace TEC\Tickets\Commerce\Gateways\Square;

use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_WhoDat;
use TEC\Tickets\Commerce\Gateways\Square\REST\On_Boarding_Endpoint;
use RuntimeException;
/**
 * Class WhoDat. Handles connection to Square when the platform keys are needed.
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square
 */
class WhoDat extends Abstract_WhoDat {

	/**
	 * The API Path.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	protected const API_ENDPOINT = 'commerce/v1/square';

	/**
	 * The nonce action for the state.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	protected const STATE_NONCE_ACTION = 'tec-tc-square-connect';

	/**
	 * Creates a new account link for the client and redirects the user to setup the account details.
	 *
	 * @since 5.24.0
	 *
	 * @param bool $is_wizard Whether this is in the wizard context.
	 *
	 * @return string
	 */
	public function connect_account( bool $is_wizard = false ): string {
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
			'url'                   => tribe( On_Boarding_Endpoint::class )->get_return_url( null, $is_wizard ),
			'state'                 => $nonce,
		];

		$connection_response = $this->get_with_cache( 'oauth/authorize', $query_args );

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
	 * @since 5.24.0
	 *
	 * @return string
	 */
	public function get_state_nonce_action(): string {
		return self::STATE_NONCE_ACTION;
	}

	/**
	 * Get the return URL for OAuth redirects.
	 *
	 * @since 5.24.0
	 *
	 * @return string
	 */
	public function get_return_url(): string {
		return rest_url( 'tribe/tickets/v1/commerce/square/on-boarding' );
	}

	/**
	 * De-authorize the current seller account in Square oAuth.
	 *
	 * @since 5.24.0
	 *
	 * @return ?array
	 */
	public function disconnect_account(): ?array {
		$merchant = tribe( Merchant::class );

		$query_args = [
			'access_token' => $merchant->get_access_token(),
		];

		return $this->post( 'oauth/token/revoke', $query_args );
	}

	/**
	 * Requests WhoDat to refresh the oAuth tokens.
	 *
	 * @since 5.24.0
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
	 * @since 5.24.0
	 *
	 * @return array|null
	 */
	public function get_token_status(): ?array {
		$merchant = tribe( Merchant::class );

		$query_args = [
			'access_token' => $merchant->get_access_token(),
			'mode'         => $merchant->get_mode(),
		];

		return $this->get_with_cache( 'oauth/token/status', $query_args );
	}

	/**
	 * Get merchant information from Square.
	 *
	 * @since 5.24.0
	 *
	 * @param string $merchant_id The merchant ID.
	 *
	 * @return array|null
	 */
	public function get_merchant( string $merchant_id ): ?array {
		$query_args = [
			'merchant_id' => $merchant_id,
		];

		return $this->get_with_cache( 'merchants/' . $merchant_id, $query_args );
	}

	/**
	 * Register a webhook endpoint with WhoDat.
	 *
	 * @since 5.24.0
	 *
	 * @param string $endpoint_url The webhook endpoint URL.
	 * @param string $merchant_id  The merchant ID.
	 *
	 * @return array The webhook data or null if the request fails.
	 *
	 * @throws RuntimeException If the webhook registration fails.
	 */
	public function register_webhook_endpoint( string $endpoint_url, string $merchant_id ): array {
		$query_args = [
			'url'         => $endpoint_url,
			'merchant_id' => $merchant_id,
		];

		$response = $this->post( 'webhooks/register', $query_args );

		if ( empty( $response['subscription'] ) ) {
			do_action(
				'tribe_log',
				'error',
				'Failed to register Square webhook',
				[
					'source' => 'tickets-commerce',
					'error'  => $response,
				]
			);

			throw new RuntimeException( __( 'Failed to register Square webhook', 'event-tickets' ), 3 );
		}

		return $response['subscription'];
	}

	/**
	 * Get required OAuth scopes for the Square integration.
	 *
	 * @since 5.24.0
	 *
	 * @param bool $include_descriptions Whether to include detailed descriptions for each scope.
	 *
	 * @return array|null Array of required scopes or null if the request fails.
	 */
	public function get_required_scopes( bool $include_descriptions = false ): ?array {
		$query_args = [
			'include_descriptions' => (bool) $include_descriptions,
		];

		$response = $this->get_with_cache( 'oauth/scopes', $query_args );

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
	 * @since 5.24.0
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
	 * @since 5.24.0
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

		$connection_response = $this->get_with_cache( 'oauth/authorize', $query_args );

		return $connection_response['auth_url'] ?? '';
	}
}
