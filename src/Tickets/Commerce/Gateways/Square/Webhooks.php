<?php
/**
 * Square Webhooks Controller.
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square
 */

namespace TEC\Tickets\Commerce\Gateways\Square;

use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Webhooks;
use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Gateway;
use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Merchant;
use TEC\Tickets\Commerce\Gateways\Square\WhoDat;
use WP_Error;
use Tribe__Date_Utils as Dates;
use RuntimeException;
use DateTimeInterface;

/**
 * Class Webhooks
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square
 */
class Webhooks extends Abstract_Webhooks {
	/**
	 * Option key for storing webhook secret key.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	public const OPTION_WEBHOOK_SECRET = 'tec-tickets-commerce-square-webhook-secret';

	/**
	 * Parameter key for storing webhook secret key on the URL.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	public const PARAM_WEBHOOK_KEY = 'tec-tc-key';

	/**
	 * Option key for storing webhook data received from the API.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	public const OPTION_WEBHOOK = 'tickets-commerce-square-webhook';

	/**
	 * Gets the gateway for this webhook.
	 *
	 * @since 5.24.0
	 *
	 * @return Abstract_Gateway
	 */
	public function get_gateway(): Abstract_Gateway {
		return tribe( Gateway::class );
	}

	/**
	 * Gets the merchant for this webhook.
	 *
	 * @since 5.24.0
	 *
	 * @return Abstract_Merchant
	 */
	public function get_merchant(): Abstract_Merchant {
		return tribe( Merchant::class );
	}

	/**
	 * Register the service provider.
	 *
	 * @since 5.24.0
	 */
	public function do_register(): void {
		add_action( 'wp_ajax_tec_tickets_commerce_square_register_webhook', [ $this, 'ajax_register_webhook' ] );
		add_action( 'init', [ $this, 'schedule_webhook_registration_refresh' ] );
		add_action( 'tec_tickets_commerce_square_refresh_webhook', [ $this, 'refresh_webhook' ] );
	}

	/**
	 * Unregister hooks and cleanup.
	 *
	 * @since 5.24.0
	 */
	public function unregister(): void {
		remove_action( 'wp_ajax_tec_tickets_commerce_square_register_webhook', [ $this, 'ajax_register_webhook' ] );
		remove_action( 'init', [ $this, 'schedule_webhook_registration_refresh' ] );
		remove_action( 'tec_tickets_commerce_square_refresh_webhook', [ $this, 'refresh_webhook' ] );
	}

	/**
	 * Schedule a webhook registration refresh.
	 *
	 * @since 5.24.0
	 */
	public function schedule_webhook_registration_refresh(): void {
		if ( ! $this->is_webhook_healthy() ) {
			return;
		}

		if ( as_has_scheduled_action( 'tec_tickets_commerce_square_refresh_webhook', [], 'tec-tickets-commerce-webhooks' ) ) {
			return;
		}

		as_schedule_single_action( time() + 6 * HOUR_IN_SECONDS, 'tec_tickets_commerce_square_refresh_webhook', [], 'tec-tickets-commerce-webhooks' );
	}

	/**
	 * Refresh the webhook.
	 *
	 * @since 5.24.0
	 */
	public function refresh_webhook(): void {
		if ( ! $this->is_webhook_healthy() ) {
			return;
		}

		if ( ! $this->should_refresh_webhook() ) {
			return;
		}

		$this->register_webhook_endpoint();
	}

	/**
	 * Get the webhook secret.
	 *
	 * @since 5.24.0
	 *
	 * @param bool $hash       Whether to hash the secret key.
	 * @param bool $regenerate Whether to regenerate the webhook secret key.
	 *
	 * @return string The webhook secret.
	 */
	public function get_webhook_secret( bool $hash = true, bool $regenerate = false ): string {
		$webhook_secret = $regenerate ? null : get_transient( self::OPTION_WEBHOOK_SECRET );

		if ( ! ( $webhook_secret && is_string( $webhook_secret ) ) ) {
			if ( ! $regenerate ) {
				return '';
			}

			$webhook_secret = wp_generate_password( 64, true, true );

			// We specifically save the raw secret key, not the hashed version, so that if the salt changes the webhooks fail.
			set_transient( self::OPTION_WEBHOOK_SECRET, $webhook_secret, 2 * DAY_IN_SECONDS );
		}

		return $hash ? wp_hash_password( $webhook_secret ) : $webhook_secret;
	}

	/**
	 * Get the webhook endpoint URL, with optional filtering.
	 *
	 * @since 5.24.0
	 *
	 * @param bool $regenerate Whether to regenerate the webhook secret key.
	 *
	 * @return string The webhook endpoint URL.
	 */
	public function get_webhook_endpoint_url( bool $regenerate = false ): string {
		$endpoint_url = $this->container->get( REST\Webhook_Endpoint::class )->get_route_url();

		// Allow overriding via constant for local development.
		if ( defined( 'TEC_TICKETS_COMMERCE_SQUARE_WEBHOOK_DOMAIN' ) ) {
			$domain_route = constant( 'TEC_TICKETS_COMMERCE_SQUARE_WEBHOOK_DOMAIN' );

			// Replace the home URL with the domain route.
			$endpoint_url = str_replace( home_url(), $domain_route, $endpoint_url );
		}

		// Add the webhook secret key to the URL.
		$endpoint_url = add_query_arg( self::PARAM_WEBHOOK_KEY, $this->get_webhook_secret( true, $regenerate ), $endpoint_url );

		/**
		 * Filters the Square webhook endpoint URL.
		 *
		 * Allows for overriding the webhook URL, particularly useful for local development
		 * with services like ngrok. You can define a constant TEC_TICKETS_COMMERCE_SQUARE_WEBHOOK_URL
		 * to override this value.
		 *
		 * @since 5.24.0
		 *
		 * @param string $endpoint_url The webhook endpoint URL.
		 */
		return (string) apply_filters( 'tec_tickets_commerce_square_webhook_endpoint_url', $endpoint_url );
	}

	/**
	 * Register a webhook endpoint with WhoDat.
	 *
	 * @since 5.24.0
	 *
	 * @return array<string,mixed>|WP_Error The webhook data or WP_Error on failure.
	 */
	public function register_webhook_endpoint() {
		$endpoint_url = $this->get_webhook_endpoint_url( true );
		$merchant_id  = tribe( Merchant::class )->get_merchant_id();

		try {
			// Now register the new webhook with the appropriate event types and API version.
			$subscription = tribe( WhoDat::class )->register_webhook_endpoint( $endpoint_url, $merchant_id );
		} catch ( RuntimeException $e ) {
			return new WP_Error( 'tec_tickets_commerce_square_webhook_registration_failed', $e->getMessage() );
		}

		// Store the webhook ID and signature.
		if ( empty( $subscription['id'] ) ) {
			return new WP_Error( 'tec_tickets_commerce_square_webhook_registration_failed', __( 'Failed to register webhook endpoint for Square. Please check your connection settings and try again.', 'event-tickets' ) );
		}

		$subscription['fetched_at'] = Dates::build_date_object()->format( Dates::DBDATETIMEFORMAT );

		tribe_update_option( self::OPTION_WEBHOOK, $subscription );

		return $subscription;
	}

	/**
	 * Get the webhook data.
	 *
	 * @since 5.24.0
	 *
	 * @return array<string,mixed> {
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
	protected function get_webhook(): array {
		$webhook = tribe_get_option( self::OPTION_WEBHOOK );

		if ( empty( $webhook['id'] ) || ! is_array( $webhook ) ) {
			return [];
		}

		return $webhook;
	}

	/**
	 * Get the webhook ID.
	 *
	 * @since 5.24.0
	 *
	 * @return string|null The webhook ID or null if not set.
	 */
	public function get_webhook_id(): ?string {
		return $this->get_webhook()['id'] ?? null;
	}

	/**
	 * Get the fetched date.
	 *
	 * @since 5.24.0
	 *
	 * @return DateTimeInterface|null The fetched date or null if not set.
	 */
	public function get_fetched_date(): ?DateTimeInterface {
		return ! empty( $this->get_webhook()['fetched_at'] ) ? Dates::build_date_object( $this->get_webhook()['fetched_at'] ) : null;
	}

	/**
	 * Verify webhook signature.
	 *
	 * @since 5.24.0
	 *
	 * @param string $received_secret_key The secret key from the request.
	 *
	 * @return bool Whether the signature is valid.
	 */
	public function verify_signature( string $received_secret_key ): bool {
		if ( ! $received_secret_key ) {
			return false;
		}

		if ( empty( $this->get_webhook()['id'] ) ) {
			return false;
		}

		$unhashed_key = $this->get_webhook_secret( false );

		if ( ! $unhashed_key ) {
			return false;
		}

		// Both keys need to be the same.
		return wp_check_password( $unhashed_key, $received_secret_key );
	}

	/**
	 * Verify whodat signature.
	 *
	 * @since 5.24.0
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
	 * @since 5.24.0
	 *
	 * @return void
	 */
	public function ajax_register_webhook(): void {
		// Verify nonce.
		if ( ! wp_verify_nonce( tec_get_request_var( 'nonce' ), 'square-webhook-register' ) ) {
			wp_send_json_error(
				[
					'message' => __( 'Security check failed. Please refresh the page and try again.', 'event-tickets' ),
				],
				401
			);
			return;
		}

		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				[
					'message' => __( 'You do not have permission to perform this action.', 'event-tickets' ),
				],
				401
			);
			return;
		}

		// Register new webhook.
		$response = $this->register_webhook_endpoint();

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( $response, 500 );
			return;
		}

		if ( ! $response || isset( $response['errors'] ) ) {
			wp_send_json_error(
				[
					'message'  => __( 'Failed to register webhook endpoint for Square. Please check your connection settings and try again.', 'event-tickets' ),
					'response' => $response,
				],
				500
			);
			return;
		}

		wp_send_json_success(
			[
				'message' => __( 'Webhook endpoint successfully registered with Square.', 'event-tickets' ),
			],
			200
		);
	}

	/**
	 * Check if the webhook is healthy.
	 *
	 * @since 5.24.0
	 *
	 * @return bool Whether the webhook is healthy.
	 */
	public function is_webhook_healthy(): bool {
		return (bool) ( $this->get_webhook()['id'] ?? false );
	}

	/**
	 * Check if the webhook is expired.
	 *
	 * @since 5.24.0
	 *
	 * @return bool Whether the webhook is expired.
	 */
	public function is_webhook_expired(): bool {
		$webhook = $this->get_webhook();

		if ( empty( $webhook['id'] ) ) {
			return true;
		}

		$expires_at = $webhook['expires_at'] ?? null;

		// If the webhook has never been fetched, it is expired.
		if ( empty( $expires_at ) ) {
			return true;
		}

		$expires_at_date = Dates::build_date_object( $expires_at );
		$now             = Dates::build_date_object();

		return $expires_at_date->getTimestamp() < $now->getTimestamp();
	}
	/**
	 * Check if the webhook should be refreshed, defaults to once every hour.
	 *
	 * @since 5.24.0
	 *
	 * @return bool Whether the webhook should be refreshed.
	 */
	protected function should_refresh_webhook(): bool {
		$webhook = $this->get_webhook();

		if ( empty( $webhook['id'] ) ) {
			return false;
		}

		$fetched_at = $webhook['fetched_at'] ?? null;

		// If the webhook has never been fetched, it is expired.
		if ( ! $fetched_at ) {
			return true;
		}

		if ( ! $this->get_webhook_secret() ) {
			return true;
		}

		$fetched_at_date = Dates::build_date_object( $fetched_at );
		$some_time_ago   = Dates::build_date_object()->modify( '-12 hours' );

		return $fetched_at_date->getTimestamp() < $some_time_ago->getTimestamp();
	}
}
