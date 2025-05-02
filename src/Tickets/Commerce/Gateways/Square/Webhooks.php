<?php
/**
 * Square Webhooks Controller.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square
 */

namespace TEC\Tickets\Commerce\Gateways\Square;

use TEC\Tickets\Commerce\Gateways\Square\WhoDat;
use WP_Error;
use TEC\Tickets\Commerce\Gateways\Square\Notices\Webhook_Notice;
use TEC\Common\Contracts\Provider\Controller as Controller_Contract;

/**
 * Class Webhooks
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square
 */
class Webhooks extends Controller_Contract {

	/**
	 * Option key for storing webhook data received from the API.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const OPTION_WEBHOOK = 'tickets-commerce-square-webhook';

	/**
	 * Option key for storing webhook IDs.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const OPTION_WEBHOOK_ID = 'tickets-commerce-square-webhook-id';

	/**
	 * Option key for storing available event types.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const OPTION_AVAILABLE_EVENT_TYPES = 'tickets-commerce-square-webhook-event-types';

	/**
	 * Register the service provider.
	 *
	 * @since TBD
	 */
	public function do_register(): void {
		// Add AJAX handler for webhook registration.
		add_action( 'wp_ajax_tec_tickets_commerce_square_register_webhook', [ $this, 'ajax_register_webhook' ] );

		tribe( Webhook_Notice::class );
	}

	/**
	 * Unregister hooks and cleanup.
	 *
	 * @since TBD
	 */
	public function unregister(): void {
		// Add AJAX handler for webhook registration.
		remove_action( 'wp_ajax_tec_tickets_commerce_square_register_webhook', [ $this, 'ajax_register_webhook' ] );
	}

	/**
	 * Get the webhook endpoint URL, with optional filtering.
	 *
	 * @since TBD
	 *
	 * @return string The webhook endpoint URL.
	 */
	public function get_webhook_endpoint_url() {
		$endpoint_url = $this->container->get( REST\Webhook_Endpoint::class )->get_route_url();

		// Allow overriding via constant for local development.
		if ( defined( 'TEC_TICKETS_COMMERCE_SQUARE_WEBHOOK_DOMAIN' ) ) {
			$domain_route = constant( 'TEC_TICKETS_COMMERCE_SQUARE_WEBHOOK_DOMAIN' );

			// Replace the home URL with the domain route.
			$endpoint_url = str_replace( home_url(), $domain_route, $endpoint_url );
		}

		/**
		 * Filters the Square webhook endpoint URL.
		 *
		 * Allows for overriding the webhook URL, particularly useful for local development
		 * with services like ngrok. You can define a constant TEC_TICKETS_COMMERCE_SQUARE_WEBHOOK_URL
		 * to override this value.
		 *
		 * @since TBD
		 *
		 * @param string $endpoint_url The webhook endpoint URL.
		 */
		$endpoint_url = apply_filters( 'tec_tickets_commerce_square_webhook_endpoint_url', $endpoint_url );

		return $endpoint_url;
	}

	/**
	 * Register a webhook endpoint with WhoDat.
	 *
	 * @since TBD
	 *
	 * @return true|WP_Error The webhook data or WP_Error on failure.
	 */
	public function register_webhook_endpoint() {
		$endpoint_url = $this->get_webhook_endpoint_url();
		$merchant_id  = tribe( Merchant::class )->get_merchant_id();

		// Now register the new webhook with the appropriate event types and API version.
		$response = tribe( WhoDat::class )->register_webhook_endpoint( $endpoint_url, $merchant_id );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( empty( $response['subscription'] ) ) {
			return new WP_Error( 'tec_tickets_commerce_square_webhook_registration_failed', __( 'Failed to register webhook endpoint for Square. Please check your connection settings and try again.', 'event-tickets' ) );
		}

		$subscription = $response['subscription'];

		// Store the webhook ID and signature.
		if ( empty( $subscription['id'] ) ) {
			return new WP_Error( 'tec_tickets_commerce_square_webhook_registration_failed', __( 'Failed to register webhook endpoint for Square. Please check your connection settings and try again.', 'event-tickets' ) );
		}

		tribe_update_option( self::OPTION_WEBHOOK, $subscription );
		tribe_update_option( self::OPTION_WEBHOOK_ID, $subscription['id'] );

		return true;
	}

	/**
	 * Get the webhook data.
	 *
	 * @since TBD
	 *
	 * @return array<string,mixed>|null {
	 *     The webhook data or null if not set.
	 *     @type string       $id               The webhook subscription ID.
	 *     @type string       $name             The webhook subscription name.
	 *     @type bool         $enabled          Whether the subscription is enabled.
	 *     @type array        $event_types      Array of event types being subscribed to.
	 *     @type string       $notification_url The URL that will receive the webhook notifications.
	 *     @type string       $api_version      The Square API version being used.
	 *     @type string       $signature_key    The key used to verify webhook signatures.
	 *     @type string       $created_at       The timestamp when the subscription was created.
	 *     @type string       $updated_at       The timestamp when the subscription was last updated.
	 * }
	 */
	public function get_webhook(): ?array {
		$webhook = tribe_get_option( self::OPTION_WEBHOOK );

		if ( empty( $webhook ) || ! is_array( $webhook ) ) {
			return null;
		}

		return $webhook;
	}

	/**
	 * Get the webhook ID.
	 *
	 * @since TBD
	 *
	 * @return string|null The webhook ID or null if not set.
	 */
	public function get_webhook_id(): ?string {
		$webhook_id = tribe_get_option( self::OPTION_WEBHOOK_ID );

		if ( empty( $webhook_id ) ) {
			return null;
		}

		return (string) $webhook_id;
	}

	/**
	 * Compare two URLs to see if they match, ignoring protocol and trailing slashes.
	 *
	 * @since TBD
	 *
	 * @param string $url1 The first URL to compare.
	 * @param string $url2 The second URL to compare.
	 *
	 * @return bool Whether the URLs match.
	 */
	protected function urls_match( $url1, $url2 ) {
		// Remove protocol.
		$url1 = preg_replace( '#^https?://#', '', $url1 );
		$url2 = preg_replace( '#^https?://#', '', $url2 );

		// Remove trailing slashes.
		$url1 = untrailingslashit( $url1 );
		$url2 = untrailingslashit( $url2 );

		return $url1 == $url2;
	}

	/**
	 * Get the latest available event types from the API or cache.
	 *
	 * @since TBD
	 *
	 * @param bool $force_refresh Whether to force a refresh from the API.
	 *
	 * @return array {
	 *     Array containing available event types data
	 *
	 *     @type array  $types         Array of available event types.
	 *     @type string $api_version   The API version returned by the endpoint.
	 * }
	 */
	public function get_available_event_types( bool $force_refresh = false ): array {
		// Try to get from cache first unless forcing refresh.
		if ( ! $force_refresh ) {
			$cached_data = tribe_get_option( self::OPTION_AVAILABLE_EVENT_TYPES );
			if ( ! empty( $cached_data ) && isset( $cached_data['last_updated'] ) && $cached_data['last_updated'] > ( time() - DAY_IN_SECONDS ) ) {
				return [
					'types'       => $cached_data['types'] ?? [],
					'api_version' => $cached_data['api_version'] ?? '',
				];
			}
		}

		// Fetch from API.
		$whodat   = tribe( WhoDat::class );
		$response = $whodat->get_available_event_types();

		$result = [
			'types'       => [],
			'api_version' => '',
		];

		if ( ! empty( $response ) ) {
			// The WhoDat class returns 'event_types' key for the array of event types.
			$result['types']       = $response['event_types'] ?? [];
			$result['api_version'] = $response['api_version'] ?? '';

			// Cache the result with a timestamp.
			tribe_update_option(
				self::OPTION_AVAILABLE_EVENT_TYPES,
				[
					'types'        => $result['types'],
					'api_version'  => $result['api_version'],
					'last_updated' => time(),
				]
			);
		}

		return $result;
	}

	/**
	 * Gets the API version to use for webhooks.
	 * Prioritizes the version from the available types endpoint, falling back to the class property.
	 *
	 * @since TBD
	 *
	 * @return string The API version to use.
	 */
	public function get_api_version(): string {
		$available_data = $this->get_available_event_types();
		$api_version    = $available_data['api_version'] ?? '2025-04-16';

		/**
		 * Filters the Square webhook API version.
		 *
		 * @since TBD
		 *
		 * @param string $api_version The API version to use for webhooks.
		 */
		return apply_filters( 'tec_tickets_commerce_square_webhook_api_version', $api_version );
	}

	/**
	 * Check if the current event types match the available payment-related events from the API.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the current event types match the available ones.
	 */
	public function is_event_types_current(): bool {
		$webhook = $this->get_webhook();

		if ( empty( $webhook ) || ! isset( $webhook['event_types'] ) ) {
			return false;
		}

		// Get event types directly from WhoDat API.
		$api_event_types = tribe( WhoDat::class )->get_available_event_types();

		if ( empty( $api_event_types ) || ! isset( $api_event_types['event_types'] ) ) {
			return false;
		}

		$payment_related_events = array_intersect( $webhook['event_types'], $api_event_types['event_types'] );

		return ! empty( $payment_related_events );
	}

	/**
	 * Check if the current API version matches the recommended version.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the current API version is up to date.
	 */
	public function is_api_version_current(): bool {
		$webhook = $this->get_webhook();

		if ( empty( $webhook ) || ! isset( $webhook['api_version'] ) ) {
			return false;
		}

		$api_event_types = tribe( WhoDat::class )->get_available_event_types();

		if ( empty( $api_event_types['api_version'] ) ) {
			return false;
		}

		return $webhook['api_version'] === $api_event_types['api_version'];
	}

	/**
	 * Verify webhook signature.
	 *
	 * @since TBD
	 *
	 * @param string $signature The signature from the request header.
	 * @param string $body      The raw request body.
	 *
	 * @return bool Whether the signature is valid.
	 */
	public function verify_signature( $signature, $body ) {
		$webhook = $this->get_webhook();

		if ( empty( $webhook ) || ! isset( $webhook['signature_key'] ) ) {
			return false;
		}

		$stored_signature = $webhook['signature_key'];

		if ( empty( $stored_signature ) || empty( $signature ) ) {
			return false;
		}

		// Compute HMAC with SHA-256.
		$computed_signature = hash_hmac( 'sha256', $body, $stored_signature );

		return hash_equals( $signature, $computed_signature );
	}

	/**
	 * AJAX handler for webhook registration.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function ajax_register_webhook(): void {
		// Verify nonce.
		if ( ! wp_verify_nonce( tec_get_request_var( 'nonce' ), 'square-webhook-register' ) ) {
			wp_send_json_error(
				[
					'message' => __( 'Security check failed. Please refresh the page and try again.', 'event-tickets' ),
				]
			);
		}

		// Check user capabilities.
		if (
			! current_user_can( 'manage_options' )
		) {
			wp_send_json_error(
				[
					'message' => __( 'You do not have permission to perform this action.', 'event-tickets' ),
				]
			);
		}

		// Register new webhook.
		$response = $this->register_webhook_endpoint();

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( $response );
		}

		if (
			empty( $response ) ||
			isset( $response['error'] )
		) {
			wp_send_json_error(
				[
					'message'  => __( 'Failed to register webhook endpoint for Square. Please check your connection settings and try again.', 'event-tickets' ),
					'response' => $response,
				]
			);
		}

		wp_send_json_success(
			[
				'message' => __( 'Webhook endpoint successfully registered with Square.', 'event-tickets' ),
			]
		);
	}

	/**
	 * Check if the webhook is healthy.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the webhook is healthy.
	 */
	public function is_webhook_healthy(): bool {
		$webhook = $this->get_webhook();

		if ( empty( $webhook ) || ! isset( $webhook['id'] ) ) {
			return false;
		}

		return true;
	}
}
