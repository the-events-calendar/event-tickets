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
	}

	/**
	 * Unregister hooks and cleanup.
	 *
	 * @since TBD
	 */
	public function unregister(): void {
		remove_action( 'init', [ $this, 'register_cron_events' ] );
		remove_action( 'tec_tickets_commerce_square_check_webhooks', [ $this, 'check_webhook_health' ] );
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
	 * Register a webhook with Square.
	 *
	 * @since TBD
	 *
	 * @return array|null The webhook data or null on failure.
	 */
	public function register_webhook() {
		$endpoint_url = rest_url( 'tribe/tickets/v1/commerce/square/webhooks' );
		$who_dat = tribe( WhoDat::class );

		$response = $who_dat->register_webhook( $endpoint_url, $this->event_types );

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

		// Store the webhook ID and signature
		if ( ! empty( $response['id'] ) ) {
			tribe_update_option( self::$option_webhook_id, $response['id'] );
		}

		if ( ! empty( $response['signature_key'] ) ) {
			tribe_update_option( self::$option_webhook_signature, $response['signature_key'] );
		}

		// Update last check status
		$this->update_webhook_health_status( true );

		return $response;
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
	 * Check webhook health by testing the connection.
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
}
