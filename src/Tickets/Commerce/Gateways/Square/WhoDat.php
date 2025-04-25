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

	/**
	 * Register a webhook subscription for the merchant.
	 *
	 * @since TBD
	 *
	 * @param string $notification_url The URL to send webhook notifications to.
	 * @param array  $event_types      The event types to subscribe to.
	 * @param string $api_version      The API version to use, defaults to '2023-12-13'.
	 *
	 * @return array|null
	 */
	public function register_webhook( string $notification_url, array $event_types = [], string $api_version = '2023-12-13' ): ?array {
		$merchant = tribe( Merchant::class );

		$query_args = [
			'access_token' => $merchant->get_access_token(),
			'mode'         => $merchant->get_mode(),
		];

		$body = [
			'notification_url' => $notification_url,
			'api_version'      => $api_version,
		];

		// Add event types if provided.
		if ( ! empty( $event_types ) ) {
			$body['event_types'] = $event_types;
		}

		$request_arguments = [
			'body'    => $body,
			'headers' => [
				'Content-Type' => 'application/json',
			],
		];

		return $this->post( 'webhooks/subscriptions', $query_args, $request_arguments );
	}

	/**
	 * Get all webhook subscriptions for the merchant.
	 *
	 * @since TBD
	 *
	 * @return array|null
	 */
	public function get_webhooks(): ?array {
		$merchant = tribe( Merchant::class );

		$query_args = [
			'access_token' => $merchant->get_access_token(),
			'mode'         => $merchant->get_mode(),
		];

		return $this->get( 'webhooks/subscriptions', $query_args );
	}

	/**
	 * Delete a webhook subscription.
	 *
	 * @since TBD
	 *
	 * @param string $subscription_id The ID of the webhook subscription to delete.
	 *
	 * @return array|null
	 */
	public function delete_webhook( string $subscription_id ): ?array {
		$merchant = tribe( Merchant::class );

		$query_args = [
			'access_token' => $merchant->get_access_token(),
			'mode'         => $merchant->get_mode(),
		];

		$body = [
			'subscription_id' => $subscription_id,
		];

		$request_arguments = [
			'body'    => $body,
			'headers' => [
				'Content-Type' => 'application/json',
			],
		];

		return $this->post( 'webhooks/subscriptions/delete', $query_args, $request_arguments );
	}

	/**
	 * Check if the existing webhook configuration matches the current requirements.
	 * Compares event types and API version to determine if the webhook needs updating.
	 *
	 * @since TBD
	 *
	 * @param string      $webhook_id   The ID of the webhook to check.
	 * @param array       $event_types  The expected event types.
	 * @param string|null $api_version  The expected API version, if null will use the latest available.
	 *
	 * @return array {
	 *     Array containing verification results
	 *
	 *     @type bool   $is_current         Whether the webhook is up to date.
	 *     @type array  $missing_events     Array of missing event types, if any.
	 *     @type bool   $version_mismatch   Whether the API version is different.
	 *     @type string $current_version    The current API version of the webhook.
	 *     @type array  $webhook_data       The full webhook data retrieved.
	 * }
	 */
	public function check_webhook_configuration( string $webhook_id, array $event_types, ?string $api_version = null ): array {
		// Default result structure.
		$result = [
			'is_current'       => false,
			'missing_events'   => [],
			'version_mismatch' => false,
			'current_version'  => '',
			'webhook_data'     => null,
		];

		// If no API version is provided, fetch the latest one.
		if ( null === $api_version ) {
			$event_types_data = $this->get_available_event_types();
			$api_version      = $event_types_data['api_version'] ?? '2025-04-16';
		}

		// Get all webhooks.
		$webhooks = $this->get_webhooks();

		if ( empty( $webhooks ) || empty( $webhooks['subscriptions'] ) ) {
			return $result;
		}

		$target_webhook = null;

		// Find our specific webhook.
		foreach ( $webhooks['subscriptions'] as $subscription ) {
			if ( isset( $subscription['id'] ) && $subscription['id'] === $webhook_id ) {
				$target_webhook = $subscription;
				break;
			}
		}

		// If webhook not found.
		if ( empty( $target_webhook ) ) {
			return $result;
		}

		// Store the webhook data for reference.
		$result['webhook_data'] = $target_webhook;

		// Check API version.
		$current_version           = $target_webhook['api_version'] ?? '';
		$result['current_version'] = $current_version;

		if ( $current_version !== $api_version ) {
			$result['version_mismatch'] = true;
		}

		// Check event types.
		$current_events = $target_webhook['event_types'] ?? [];

		// Check for missing events.
		$missing_events           = array_diff( $event_types, $current_events );
		$result['missing_events'] = $missing_events;

		// Webhook is current if API version matches and no missing events.
		$result['is_current'] = ! $result['version_mismatch'] && empty( $missing_events );

		return $result;
	}

	/**
	 * Get available webhook event types from the API.
	 *
	 * @since TBD
	 *
	 * @return array|null Array containing available event types and API version or null if the request fails.
	 */
	public function get_available_event_types(): ?array {
		$merchant = tribe( Merchant::class );

		$query_args = [
			'access_token' => $merchant->get_access_token(),
			'mode'         => $merchant->get_mode(),
		];

		$response = $this->get( 'webhooks/event-types', $query_args );

		if ( empty( $response ) || isset( $response['error'] ) ) {
			do_action(
				'tribe_log',
				'error',
				'Failed to retrieve Square webhook event types',
				[
					'source'   => 'tickets-commerce-square',
					'response' => $response ?? 'Empty response',
				]
			);

			return null;
		}

		// Ensure the response has a standardized format with both event types and API version.
		return [
			'event_types' => $response['event_types'] ?? [],
			'api_version' => $response['api_version'] ?? '',
		];
	}
}
