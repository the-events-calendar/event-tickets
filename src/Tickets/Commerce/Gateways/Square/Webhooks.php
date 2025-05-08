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
use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use Tribe__Date_Utils as Dates;
use RuntimeException;

/**
 * Class Webhooks
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square
 */
class Webhooks extends Controller_Contract {
	/**
	 * Option key for storing webhook secret key.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const OPTION_WEBHOOK_SECRET = 'tec-tickets-commerce-square-webhook-secret';

	/**
	 * Parameter key for storing webhook secret key on the URL.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const PARAM_WEBHOOK_KEY = 'tec-tc-key';

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
	 * Register the service provider.
	 *
	 * @since TBD
	 */
	public function do_register(): void {
		// Add AJAX handler for webhook registration.
		add_action( 'wp_ajax_tec_tickets_commerce_square_register_webhook', [ $this, 'ajax_register_webhook' ] );
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
	 * Get the webhook secret.
	 *
	 * @since TBD
	 *
	 * @param bool $hash Whether to hash the secret key.
	 *
	 * @return string The webhook secret.
	 */
	public function get_webhook_secret( bool $hash = true ): string {
		$webhook_secret = get_option( self::OPTION_WEBHOOK_SECRET );

		if ( empty( $webhook_secret ) ) {
			$webhook_secret = wp_generate_password( 64, true, true );

			// We specifically save the raw secret key, not the hashed version, so that if the salt changes the webhooks fail.
			update_option( self::OPTION_WEBHOOK_SECRET, $webhook_secret );
		}

		return $hash ? wp_hash_password( $webhook_secret ) : $webhook_secret;
	}

	/**
	 * Get the webhook endpoint URL, with optional filtering.
	 *
	 * @since TBD
	 *
	 * @return string The webhook endpoint URL.
	 */
	public function get_webhook_endpoint_url(): string {
		$endpoint_url = $this->container->get( REST\Webhook_Endpoint::class )->get_route_url();

		// Allow overriding via constant for local development.
		if ( defined( 'TEC_TICKETS_COMMERCE_SQUARE_WEBHOOK_DOMAIN' ) ) {
			$domain_route = constant( 'TEC_TICKETS_COMMERCE_SQUARE_WEBHOOK_DOMAIN' );

			// Replace the home URL with the domain route.
			$endpoint_url = str_replace( home_url(), $domain_route, $endpoint_url );
		}

		// Add the webhook secret key to the URL.
		$endpoint_url = add_query_arg( self::PARAM_WEBHOOK_KEY, $this->get_webhook_secret( true ), $endpoint_url );

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
		$endpoint_url = (string) apply_filters( 'tec_tickets_commerce_square_webhook_endpoint_url', $endpoint_url );

		return $endpoint_url;
	}

	/**
	 * Register a webhook endpoint with WhoDat.
	 *
	 * @since TBD
	 *
	 * @return array<string,mixed>|WP_Error The webhook data or WP_Error on failure.
	 */
	public function register_webhook_endpoint() {
		$endpoint_url = $this->get_webhook_endpoint_url();
		$merchant_id  = tribe( Merchant::class )->get_merchant_id();

		try {
			// Now register the new webhook with the appropriate event types and API version.
			$subscription = tribe( WhoDat::class )->register_webhook_endpoint( $endpoint_url, $merchant_id )['subscription'] ?? null;
		} catch ( RuntimeException $e ) {
			return new WP_Error( 'tec_tickets_commerce_square_webhook_registration_failed', $e->getMessage() );
		}

		// Store the webhook ID and signature.
		if ( empty( $subscription['id'] ) ) {
			return new WP_Error( 'tec_tickets_commerce_square_webhook_registration_failed', __( 'Failed to register webhook endpoint for Square. Please check your connection settings and try again.', 'event-tickets' ) );
		}

		$subscription['fetched_at'] = Dates::build_date_object()->format( Dates::DBDATETIMEFORMAT );

		tribe_update_option( self::OPTION_WEBHOOK, $subscription );
		tribe_update_option( self::OPTION_WEBHOOK_ID, $subscription['id'] );

		return $subscription;
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
	 *     @type string       $expires_at       The timestamp when the subscription will expire.
	 *     @type string       $fetched_at       The timestamp when the subscription was last fetched.
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
	 * Gets the API version to use for webhooks.
	 * Prioritizes the version from the available types endpoint, falling back to the class property.
	 *
	 * @since TBD
	 *
	 * @param bool $force_refresh Whether to force a refresh from the API.
	 * @return string|null The API version to use or null if not set.
	 */
	public function get_api_version( bool $force_refresh = false ): ?string {
		if ( $force_refresh ) {
			$this->register_webhook_endpoint();
		}

		$webhook = $this->get_webhook();

		return $webhook['api_version'] ?? null;
	}

	/**
	 * Verify webhook signature.
	 *
	 * @since TBD
	 *
	 * @param string $received_secret_key The secret key from the request.
	 *
	 * @return bool Whether the signature is valid.
	 */
	public function verify_signature( $received_secret_key ) {
		$webhook = $this->get_webhook();

		if ( empty( $webhook ) || ! isset( $webhook['signature_key'] ) ) {
			return false;
		}

		$unhashed_key = $this->get_webhook_secret( false );

		// Both keys need to be the same.
		return wp_check_password( $unhashed_key, $received_secret_key );
	}

	/**
	 * Verify whodat signature.
	 *
	 * @since TBD
	 *
	 * @param string $payload       The payload from the request.
	 * @param string $received_hash The hash from the request.
	 * @param string $secret_key    The secret key from the request.
	 *
	 * @return bool Whether the signature is valid.
	 */
	public function verify_whodat_signature( string $payload, string $received_hash, string $secret_key ) {
		$signature        = tribe( Merchant::class )->get_whodat_signature();
		$notification_url = add_query_arg( self::PARAM_WEBHOOK_KEY, $secret_key, untrailingslashit( $this->get_webhook_endpoint_url() ) );

		if ( ! ( $signature && $notification_url && $payload ) ) {
			return false;
		}

		// Convert the payload to UTF-8.
		$payload = function_exists( 'mb_convert_encoding' ) ? mb_convert_encoding( $payload, 'UTF-8' ) : $payload;

		return md5( "{$notification_url}.{$payload}.{$signature}" ) === $received_hash;
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
			isset( $response['errors'] )
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

	/**
	 * Check if the webhook is expired.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the webhook is expired.
	 */
	public function is_webhook_expired(): bool {
		$webhook = $this->get_webhook();

		if ( empty( $webhook ) || ! isset( $webhook['id'] ) ) {
			return false;
		}

		$expires_at = $webhook['expires_at'] ?? null;

		// If the webhook has never been fetched, it is expired.
		if ( empty( $expires_at ) ) {
			return true;
		}

		$expires_at_date = Dates::build_date_object( $expires_at );
		$now = Dates::build_date_object();

		return $expires_at_date->getTimestamp() < $now->getTimestamp();
	}
	/**
	 * Check if the webhook should be refreshed, defaults to once every hour.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the webhook should be refreshed.
	 */
	public function should_refresh_webhook(): bool {
		$webhook = $this->get_webhook();

		if ( empty( $webhook ) || ! isset( $webhook['id'] ) ) {
			return false;
		}

		$fetched_at = $webhook['fetched_at'] ?? null;

		// If the webhook has never been fetched, it is expired.
		if ( empty( $fetched_at ) ) {
			return true;
		}

		$fetched_at_date = Dates::build_date_object( $fetched_at );
		$now = Dates::build_date_object();

		$one_hour_ago = $now->modify( '-1 hour' );

		return $fetched_at_date->getTimestamp() < $one_hour_ago->getTimestamp();
	}
}
