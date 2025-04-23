<?php

namespace TEC\Tickets\Commerce\Gateways\Square;

use TEC\Tickets\Commerce\Gateways\Square\Notices\Webhook_Notice;
use TEC\Tickets\Commerce\Gateways\Square\WhoDat;
use Tribe__Utils__Array as Arr;

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
	 * Option key for storing webhook signatures.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $option_webhook_signature = 'tickets-commerce-square-webhook-signature';

	/**
	 * Option key for storing webhook IDs.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $option_webhook_id = 'tickets-commerce-square-webhook-id';

	/**
	 * Option key for storing webhook last check status.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $option_webhook_last_check = 'tickets-commerce-square-webhook-last-check';

	/**
	 * Option key for storing webhook configuration status.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $option_webhook_configuration = 'tickets-commerce-square-webhook-configuration';

	/**
	 * Option key for storing available event types.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $option_available_event_types = 'tickets-commerce-square-available-event-types';

	/**
	 * Square API version used for webhooks.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected $api_version = '2023-12-13';

	/**
	 * Event types to subscribe to.
	 *
	 * @since TBD
	 *
	 * @var array
	 */
	protected $event_types = [
		'payment.created',
		'payment.updated',
		'refund.created',
		'refund.updated',
	];

	/**
	 * Register the service provider.
	 *
	 * @since TBD
	 */
	public function do_register(): void {
		// Add cron event for webhook health check
		add_action( 'init', [ $this, 'register_cron_events' ] );
		add_action( 'tec_tickets_commerce_square_check_webhooks', [ $this, 'check_webhook_health' ] );

		// Add AJAX handler for webhook registration
		add_action( 'wp_ajax_tec_tickets_commerce_square_register_webhook', [ $this, 'ajax_register_webhook' ] );

		// Run initial checks for admin pages
		add_action( 'admin_init', [ $this, 'maybe_run_initial_checks' ] );
	}

	/**
	 * Unregister hooks and cleanup.
	 *
	 * @since TBD
	 */
	public function unregister(): void {
		remove_action( 'init', [ $this, 'register_cron_events' ] );
		remove_action( 'tec_tickets_commerce_square_check_webhooks', [ $this, 'check_webhook_health' ] );
		remove_action( 'admin_init', [ $this, 'maybe_run_initial_checks' ] );
		wp_clear_scheduled_hook( 'tec_tickets_commerce_square_check_webhooks' );
	}

	/**
	 * Register cron events for webhook health check.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function register_cron_events() {
		if ( ! wp_next_scheduled( 'tec_tickets_commerce_square_check_webhooks' ) ) {
			wp_schedule_event( time(), 'daily', 'tec_tickets_commerce_square_check_webhooks' );
		}
	}

	/**
	 * Get the webhook endpoint URL, with optional filtering.
	 *
	 * @since TBD
	 *
	 * @return string The webhook endpoint URL.
	 */
	public function get_webhook_endpoint_url() {
		$endpoint_url = rest_url( 'tribe/tickets/v1/commerce/square/webhooks' );

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
	 * Register a webhook with Square.
	 *
	 * @since TBD
	 *
	 * @return array|null The webhook data or null on failure.
	 */
	public function register_webhook() {
		$endpoint_url = $this->get_webhook_endpoint_url();
		$who_dat = tribe( WhoDat::class );

		// First check if we have any existing webhooks
		$existing_webhook_id = tribe_get_option( self::$option_webhook_id );

		// If we have a stored webhook ID, try to delete it first
		if ( ! empty( $existing_webhook_id ) ) {
			$this->unregister_webhook();
		}

		// Even if we don't have a stored ID, check for any webhooks with our endpoint URL
		$webhooks = $who_dat->get_webhooks();

		if ( ! empty( $webhooks['subscriptions'] ) ) {
			foreach ( $webhooks['subscriptions'] as $subscription ) {
				// Check if subscription has our endpoint URL
				if ( isset( $subscription['notification_url'] ) && $this->urls_match( $subscription['notification_url'], $endpoint_url ) ) {
					// Delete this webhook as it matches our URL
					$who_dat->delete_webhook( $subscription['id'] );

					do_action(
						'tribe_log',
						'info',
						'Deleted existing Square webhook with matching URL',
						[
							'source' => 'tickets-commerce-square',
							'webhook_id' => $subscription['id'],
							'url' => $subscription['notification_url'],
						]
					);
				}
			}
		}

		// Check what event types and API version we should be using
		$event_types_check = $this->check_event_types_against_available();
		$event_types_to_use = $event_types_check['is_current'] ? $this->event_types : $event_types_check['recommended_types'];
		$api_version_to_use = !empty($event_types_check['recommended_api_version'])
							? $event_types_check['recommended_api_version']
							: $this->api_version;

		// Log if we're using different configuration than current
		if ( ! $event_types_check['is_current'] ) {
			do_action(
				'tribe_log',
				'info',
				'Using updated Square webhook configuration',
				[
					'source' => 'tickets-commerce-square',
					'current_types' => $this->event_types,
					'recommended_types' => $event_types_check['recommended_types'],
					'deprecated_types' => $event_types_check['deprecated_types'],
					'new_types' => $event_types_check['new_types'],
					'current_api_version' => $this->api_version,
					'recommended_api_version' => $api_version_to_use,
				]
			);
		}

		// Now register the new webhook with the appropriate event types and API version
		$response = $who_dat->register_webhook(
			$endpoint_url,
			$event_types_to_use,
			$api_version_to_use
		);

		if ( empty( $response ) || isset( $response['error'] ) ) {
			do_action(
				'tribe_log',
				'error',
				'Failed to register Square webhook',
				[
					'source' => 'tickets-commerce-square',
					'response' => $response ?? 'Empty response',
				]
			);
			return null;
		}

		$subscription = $response['subscription'];

		// Store the webhook ID and signature
		if ( ! empty( $subscription['id'] ) ) {
			tribe_update_option( self::$option_webhook_id, $subscription['id'] );
		}

		if ( ! empty( $subscription['signature_key'] ) ) {
			tribe_update_option( self::$option_webhook_signature, $subscription['signature_key'] );
		}

		// Update last check status
		$this->update_webhook_health_status( true );

		return $response;
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
		// Remove protocol
		$url1 = preg_replace( '#^https?://#', '', $url1 );
		$url2 = preg_replace( '#^https?://#', '', $url2 );

		// Remove trailing slashes
		$url1 = rtrim( $url1, '/' );
		$url2 = rtrim( $url2, '/' );

		return $url1 === $url2;
	}

	/**
	 * Remove a webhook from Square.
	 *
	 * @since TBD
	 *
	 * @return bool Success or failure.
	 */
	public function unregister_webhook() {
		$webhook_id = tribe_get_option( self::$option_webhook_id );

		if ( empty( $webhook_id ) ) {
			return false;
		}

		$who_dat = tribe( WhoDat::class );
		$response = $who_dat->delete_webhook( $webhook_id );

		// Clean up stored webhook data
		tribe_remove_option( self::$option_webhook_id );
		tribe_remove_option( self::$option_webhook_signature );

		return empty( $response['error'] );
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
		// Try to get from cache first unless forcing refresh
		if ( ! $force_refresh ) {
			$cached_data = tribe_get_option( self::$option_available_event_types );
			if ( ! empty( $cached_data ) && isset( $cached_data['last_updated'] ) && $cached_data['last_updated'] > ( time() - DAY_IN_SECONDS ) ) {
				return [
					'types' => $cached_data['types'] ?? [],
					'api_version' => $cached_data['api_version'] ?? '',
				];
			}
		}

		// Fetch from API
		$who_dat = tribe( WhoDat::class );
		$response = $who_dat->get_available_event_types();

		$result = [
			'types' => [],
			'api_version' => '',
		];

		if ( ! empty( $response ) ) {
			$result['types'] = $response['event_types'] ?? [];
			$result['api_version'] = $response['api_version'] ?? '';

			// Cache the result with a timestamp
			tribe_update_option( self::$option_available_event_types, [
				'types' => $result['types'],
				'api_version' => $result['api_version'],
				'last_updated' => time(),
			] );
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
		$api_version = $available_data['api_version'] ?? '';

		// Fall back to the class property if no version was returned by the API
		if ( empty( $api_version ) ) {
			$api_version = $this->api_version;
		}

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
	 * Check if the current event type configuration is current compared to available types.
	 *
	 * @since TBD
	 *
	 * @return array {
	 *     Array containing verification results
	 *
	 *     @type bool   $is_current         Whether our current configuration is up to date.
	 *     @type array  $available_types    All available event types from the API.
	 *     @type array  $new_types          New event types that we're not currently using.
	 *     @type array  $deprecated_types   Event types we're using that are no longer available.
	 *     @type array  $recommended_types  Recommended event types to use.
	 *     @type string $current_api_version The current API version we're using.
	 *     @type string $recommended_api_version The API version recommended by the server.
	 *     @type bool   $api_version_current Whether our API version is current.
	 * }
	 */
	public function check_event_types_against_available(): array {
		$available_data = $this->get_available_event_types();
		$available_types = $available_data['types'];
		$recommended_api_version = $available_data['api_version'];
		$current_types = $this->event_types;
		$current_api_version = $this->api_version;

		// Check if API version is current
		$api_version_current = !empty($recommended_api_version) && $current_api_version === $recommended_api_version;

		// If we couldn't get available types, assume we're current with types but not API version
		if ( empty( $available_types ) ) {
			return [
				'is_current' => $api_version_current,
				'available_types' => [],
				'new_types' => [],
				'deprecated_types' => [],
				'recommended_types' => $current_types,
				'current_api_version' => $current_api_version,
				'recommended_api_version' => $recommended_api_version,
				'api_version_current' => $api_version_current,
			];
		}

		// Find new event types that we're not currently using
		$new_types = array_diff( $available_types, $current_types );

		// Find deprecated event types that we're still using
		$deprecated_types = array_diff( $current_types, $available_types );

		// Determine recommended event types (current minus deprecated plus new)
		$recommended_types = array_diff( $current_types, $deprecated_types );

		// For payment processing, we want to add certain event types if available
		$payment_events = [
			'payment.created',
			'payment.updated',
			'refund.created',
			'refund.updated',
		];

		foreach ( $payment_events as $event ) {
			if ( in_array( $event, $available_types, true ) && ! in_array( $event, $recommended_types, true ) ) {
				$recommended_types[] = $event;
			}
		}

		// Sort for consistency
		sort( $recommended_types );

		// Event types are current if we have no deprecated types and aren't missing recommended ones
		$event_types_current = empty( $deprecated_types ) && empty( array_diff( $recommended_types, $current_types ) );

		// Overall current status depends on both event types and API version
		$is_current = $event_types_current && $api_version_current;

		return [
			'is_current' => $is_current,
			'available_types' => $available_types,
			'new_types' => $new_types,
			'deprecated_types' => $deprecated_types,
			'recommended_types' => $recommended_types,
			'current_api_version' => $current_api_version,
			'recommended_api_version' => $recommended_api_version,
			'api_version_current' => $api_version_current,
		];
	}

	/**
	 * Check if the existing webhook configuration is current.
	 *
	 * @since TBD
	 *
	 * @return array The configuration check results.
	 */
	public function check_webhook_configuration(): array {
		$webhook_id = tribe_get_option( self::$option_webhook_id );

		// Start with a basic result structure
		$result = [
			'is_current' => false,
			'missing_events' => $this->event_types,
			'version_mismatch' => true,
			'current_version' => '',
			'webhook_data' => null,
			'event_types_current' => false,
			'event_types_check' => [],
			'last_checked' => time(),
		];

		if ( empty( $webhook_id ) ) {
			return $result;
		}

		// Check against available event types
		$event_types_check = $this->check_event_types_against_available();
		$result['event_types_check'] = $event_types_check;
		$result['event_types_current'] = $event_types_check['is_current'];

		// If event types aren't current, use the recommended types for the check
		$event_types_to_check = $event_types_check['is_current'] ? $this->event_types : $event_types_check['recommended_types'];

		// Use the recommended API version if available
		$api_version_to_check = !empty($event_types_check['recommended_api_version'])
							? $event_types_check['recommended_api_version']
							: $this->api_version;

		// Check the webhook configuration against WhoDat
		$who_dat = tribe( WhoDat::class );
		$config_check = $who_dat->check_webhook_configuration( $webhook_id, $event_types_to_check, $api_version_to_check );

		// Merge the webhook configuration check with our result
		$result = array_merge( $result, $config_check );

		// The webhook is only current if both the webhook config and event types are current
		$result['is_current'] = $config_check['is_current'] && $event_types_check['is_current'];

		// Store the complete configuration check results
		tribe_update_option( self::$option_webhook_configuration, $result );

		return $result;
	}

	/**
	 * Check webhook health by testing the connection and configuration.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the webhook is healthy.
	 */
	public function check_webhook_health() {
		$webhook_id = tribe_get_option( self::$option_webhook_id );

		if ( empty( $webhook_id ) ) {
			$this->update_webhook_health_status( false );
			return false;
		}

		$who_dat = tribe( WhoDat::class );
		$webhooks = $who_dat->get_webhooks();

		$is_healthy = false;

		// Check if our webhook ID exists in the list of webhooks
		if ( ! empty( $webhooks['subscriptions'] ) ) {
			foreach ( $webhooks['subscriptions'] as $subscription ) {
				if ( isset( $subscription['id'] ) && $subscription['id'] === $webhook_id ) {
					$is_healthy = true;
					break;
				}
			}
		}

		// Check configuration if webhook exists
		if ( $is_healthy ) {
			$config_check = $this->check_webhook_configuration();
			$is_healthy = $config_check['is_current'];
		}

		// Update the health status
		$this->update_webhook_health_status( $is_healthy );

		// If unhealthy, try to re-register
		if ( ! $is_healthy ) {
			$this->register_webhook();
		}

		return $is_healthy;
	}

	/**
	 * Update the webhook health status.
	 *
	 * @since TBD
	 *
	 * @param bool $is_healthy Whether the webhook is healthy.
	 */
	protected function update_webhook_health_status( $is_healthy ) {
		$status = [
			'is_healthy' => $is_healthy,
			'last_checked' => time(),
		];

		tribe_update_option( self::$option_webhook_last_check, $status );
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
		$stored_signature = tribe_get_option( self::$option_webhook_signature );

		if ( empty( $stored_signature ) || empty( $signature ) ) {
			return false;
		}

		// Compute HMAC with SHA-256
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
	public function ajax_register_webhook() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'square-webhook-register' ) ) {
			wp_send_json_error( [
				'message' => __( 'Security check failed. Please refresh the page and try again.', 'event-tickets' ),
			] );
		}

		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [
				'message' => __( 'You do not have permission to perform this action.', 'event-tickets' ),
			] );
		}

		// Unregister existing webhook if any
		$this->unregister_webhook();

		// Register new webhook
		$response = $this->register_webhook();

		if ( empty( $response ) || isset( $response['error'] ) ) {
			wp_send_json_error( [
				'message' => __( 'Failed to register webhook with Square. Please check your connection settings and try again.', 'event-tickets' ),
				'response' => $response,
			] );
		}

		wp_send_json_success( [
			'message' => __( 'Webhook successfully registered with Square.', 'event-tickets' ),
			'webhook_id' => tribe_get_option( self::$option_webhook_id ),
		] );
	}

	/**
	 * Maybe display admin notice for webhook configuration issues.
	 *
	 * @since TBD
	 */
	public function maybe_display_webhook_notice() {
		// Only show on Event Tickets admin pages
		if ( ! $this->is_tickets_admin_page() ) {
			return;
		}

		// Check if Square is active and connected
		$merchant = tribe( Merchant::class );
		if ( ! $merchant->is_active() ) {
			return;
		}

		$webhook_id = tribe_get_option( self::$option_webhook_id );
		if ( empty( $webhook_id ) ) {
			$this->display_missing_webhook_notice();
			return;
		}

		$config = tribe_get_option( self::$option_webhook_configuration, [] );
		if ( empty( $config ) || ! isset( $config['is_current'] ) ) {
			// Run configuration check if we don't have data
			$config = $this->check_webhook_configuration();
		}

		if ( ! $config['is_current'] ) {
			$this->display_outdated_webhook_notice( $config );
		}
	}

	/**
	 * Display notice for missing webhook.
	 *
	 * @since TBD
	 */
	protected function display_missing_webhook_notice() {
		$webhook_nonce = wp_create_nonce( 'square-webhook-register' );

		$message = sprintf(
			/* translators: %1$s: opening link tag, %2$s: closing link tag */
			esc_html__( 'Square webhooks are not configured. Webhooks are required for payment notifications. %1$sRegister webhooks%2$s.', 'event-tickets' ),
			'<a href="#" class="tec-tickets__admin-settings-square-webhook-register-trigger" data-nonce="' . esc_attr( $webhook_nonce ) . '">',
			'</a>'
		);

		echo '<div class="notice notice-warning is-dismissible"><p>' . $message . '</p></div>';
	}

	/**
	 * Display notice for outdated webhook configuration.
	 *
	 * @since TBD
	 *
	 * @param array $config The webhook configuration check results.
	 */
	protected function display_outdated_webhook_notice( $config ) {
		$issues = [];

		// Check API version mismatch in the webhook itself
		if ( $config['version_mismatch'] ) {
			$issues[] = sprintf(
				/* translators: %1$s: current version, %2$s: expected version */
				esc_html__( 'Webhook API version mismatch (current: %1$s, expected: %2$s)', 'event-tickets' ),
				esc_html( $config['current_version'] ?: __( 'unknown', 'event-tickets' ) ),
				esc_html( $this->api_version )
			);
		}

		// Check missing webhook events
		if ( ! empty( $config['missing_events'] ) ) {
			$issues[] = sprintf(
				/* translators: %s: comma-separated list of event types */
				esc_html__( 'Missing event types: %s', 'event-tickets' ),
				esc_html( implode( ', ', $config['missing_events'] ) )
			);
		}

		// Check event types against available ones
		if ( isset( $config['event_types_check'] ) ) {
			$event_types_check = $config['event_types_check'];

			// Check for API version updates from the server
			if ( isset( $event_types_check['api_version_current'] ) && ! $event_types_check['api_version_current'] ) {
				$issues[] = sprintf(
					/* translators: %1$s: current version, %2$s: recommended version */
					esc_html__( 'Square API version update available (current: %1$s, recommended: %2$s)', 'event-tickets' ),
					esc_html( $event_types_check['current_api_version'] ?: __( 'unknown', 'event-tickets' ) ),
					esc_html( $event_types_check['recommended_api_version'] ?: __( 'unknown', 'event-tickets' ) )
				);
			}

			// Check for deprecated event types
			if ( ! empty( $event_types_check['deprecated_types'] ) ) {
				$issues[] = sprintf(
					/* translators: %s: comma-separated list of event types */
					esc_html__( 'Using deprecated event types: %s', 'event-tickets' ),
					esc_html( implode( ', ', $event_types_check['deprecated_types'] ) )
				);
			}

			// Check for new available event types we should be using
			$missing_recommended = array_diff( $event_types_check['recommended_types'], $this->event_types );
			if ( ! empty( $missing_recommended ) ) {
				$issues[] = sprintf(
					/* translators: %s: comma-separated list of event types */
					esc_html__( 'New recommended event types available: %s', 'event-tickets' ),
					esc_html( implode( ', ', $missing_recommended ) )
				);
			}
		}

		$issues_text = implode( '; ', $issues );

		$webhook_nonce = wp_create_nonce( 'square-webhook-register' );

		$message = sprintf(
			/* translators: %1$s: issues list, %2$s: opening link tag, %3$s: closing link tag */
			esc_html__( 'Square webhook configuration is outdated (%1$s). %2$sUpdate webhooks%3$s.', 'event-tickets' ),
			$issues_text,
			'<a href="#" class="tec-tickets__admin-settings-square-webhook-register-trigger" data-nonce="' . esc_attr( $webhook_nonce ) . '">',
			'</a>'
		);

		echo '<div class="notice notice-warning is-dismissible"><p>' . $message . '</p></div>';
	}

	/**
	 * Check if current page is a Tickets admin page.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	protected function is_tickets_admin_page() {
		if ( ! is_admin() ) {
			return false;
		}

		$screen = get_current_screen();

		if ( empty( $screen ) ) {
			return false;
		}

		// List of screens where to show the notice
		$valid_screens = [
			'tribe_events_page_tec-tickets-settings',
			'tribe_events_page_tec-tickets-commerce-orders',
			'tribe_events_page_tickets-commerce-orders',
			'events_page_tec-tickets-settings',
			'events_page_tec-tickets-commerce-orders',
			'events_page_tickets-commerce-orders',
		];

		return in_array( $screen->id, $valid_screens, true );
	}

	/**
	 * Maybe run initial webhook checks on admin pages.
	 *
	 * @since TBD
	 */
	public function maybe_run_initial_checks(): void {
		// Only run checks in admin
		if ( ! is_admin() ) {
			return;
		}

		// Check if Square is active and connected
		$merchant = tribe( Merchant::class );
		if ( ! $merchant->is_active() ) {
			return;
		}

		// Get the last check time
		$status = tribe_get_option( self::$option_webhook_last_check, [] );
		$last_checked = isset( $status['last_checked'] ) ? (int) $status['last_checked'] : 0;

		// Run check if it hasn't been run in 12 hours
		if ( time() - $last_checked > 12 * HOUR_IN_SECONDS ) {
			$this->check_webhook_health();
		}

		// Get the last config check
		$config = tribe_get_option( self::$option_webhook_configuration, [] );
		$last_config_check = isset( $config['last_checked'] ) ? (int) $config['last_checked'] : 0;

		// Run config check if it hasn't been run in 12 hours
		if ( time() - $last_config_check > 12 * HOUR_IN_SECONDS ) {
			$this->check_webhook_configuration();
		}
	}
}
