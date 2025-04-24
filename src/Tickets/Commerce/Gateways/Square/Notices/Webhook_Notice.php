<?php
/**
 * Square Webhook Notices
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Notices
 */

namespace TEC\Tickets\Commerce\Gateways\Square\Notices;

use TEC\Tickets\Commerce\Gateways\Square\Gateway;
use TEC\Tickets\Commerce\Gateways\Square\Webhooks;

/**
 * Square Webhook Notice Class
 *
 * @since TBD
 */
class Webhook_Notice {
	/**
	 * Notice slug
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected $notice_slug = 'tec-tickets-commerce-square-webhook-notice';

	/**
	 * Setup hooks for the service provider.
	 *
	 * @since TBD
	 */
	public function register() {
		// First check the webhook status if needed.
		$this->maybe_check_webhook_status();

		tribe_notice(
			$this->notice_slug,
			[ $this, 'render_notice' ],
			[
				'type'     => 'error',
				'dismiss'  => true,
				'priority' => 10,
			],
			[ $this, 'should_display_notice' ]
		);
	}

	/**
	 * Maybe check webhook status if we haven't checked recently.
	 *
	 * @since TBD
	 */
	protected function maybe_check_webhook_status() {
		// If Square gateway is not enabled, don't perform check.
		if ( ! tribe( Gateway::class )->is_enabled() ) {
			return;
		}

		// Get the last check time.
		$status       = tribe_get_option( Webhooks::$option_webhook_last_check, [] );
		$last_checked = isset( $status['last_checked'] ) ? (int) $status['last_checked'] : 0;

		// Check if we need to run a check (not checked in the last 12 hours).
		if ( time() - $last_checked > 12 * HOUR_IN_SECONDS ) {
			// Run the webhook health check.
			tribe( Webhooks::class )->check_webhook_health();

			// Also check the webhook configuration.
			tribe( Webhooks::class )->check_webhook_configuration();
		}
	}

	/**
	 * Determines if the webhook notice should be displayed.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function should_display_notice() {
		// If Square gateway is not enabled, don't show the notice.
		if ( ! tribe( Gateway::class )->is_enabled() ) {
			return false;
		}

		// If Square gateway is not enabled, don't show the notice.
		if ( ! tribe( Gateway::class )->is_active() ) {
			return false;
		}

		// Get the webhook health status.
		$status = tribe_get_option( Webhooks::$option_webhook_last_check, [] );

		// Get the webhook configuration status.
		$config = tribe_get_option( Webhooks::$option_webhook_configuration, [] );

		// If there's no status or webhooks are healthy and configuration is current, don't show notice.
		if (
			empty( $status ) ||
			(
				( isset( $status['is_healthy'] ) && $status['is_healthy'] ) &&
				( isset( $config['is_current'] ) && $config['is_current'] )
			)
		) {
			return false;
		}

		// Only show on admin pages.
		if ( ! is_admin() ) {
			return false;
		}

		// Don't show on tickets admin pages where we already have inline notices.
		$screen = get_current_screen();
		if ( $screen && $this->is_tickets_admin_page( $screen->id ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Render the webhook notice.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function render_notice() {
		$health_status = tribe_get_option( Webhooks::$option_webhook_last_check, [] );
		$config_status = tribe_get_option( Webhooks::$option_webhook_configuration, [] );
		$webhook_id    = tribe_get_option( Webhooks::$option_webhook_id );

		$issues = [];

		// Check if webhook is missing.
		if ( empty( $webhook_id ) ) {
			$issues[] = esc_html__( 'Webhook not registered', 'event-tickets' );
		} else {
			// All other checks are only relevant if the webhook is registered.
			// Check health status.
			if ( ! empty( $health_status ) && isset( $health_status['is_healthy'] ) && ! $health_status['is_healthy'] ) {
				$issues[] = esc_html__( 'Webhook not functioning', 'event-tickets' );
			}

			// Check configuration status.
			if ( ! empty( $config_status ) ) {
				// API version issues.
				if ( isset( $config_status['version_mismatch'] ) && $config_status['version_mismatch'] ) {
					$issues[] = esc_html__( 'API version mismatch', 'event-tickets' );
				}

				// Event type issues.
				if ( isset( $config_status['event_types_check'] ) && isset( $config_status['event_types_check']['is_current'] ) && ! $config_status['event_types_check']['is_current'] ) {
					$issues[] = esc_html__( 'Event types configuration outdated', 'event-tickets' );
				}

				// Missing events.
				if ( ! empty( $config_status['missing_events'] ) ) {
					$issues[] = esc_html__( 'Missing webhook events', 'event-tickets' );
				}
			}
		}

		// If there are no issues, don't show the notice.
		if ( empty( $issues ) ) {
			return '';
		}

		$issues_text = implode( ', ', $issues );

		// Create a URL to the ticket settings page.
		$settings_url = admin_url( 'admin.php?page=tec-tickets-settings&tab=payments&section=square' );

		// Create the webhook nonce.
		$webhook_nonce = wp_create_nonce( 'square-webhook-register' );

		return sprintf(
			'<p><strong>%1$s</strong></p><p>%2$s</p><p><a href="%3$s" class="button button-primary" data-nonce="%5$s">%4$s</a></p><div class="tec-tickets__admin-settings-square-webhook-nonce" data-nonce="%5$s" style="display: none;"></div>',
			esc_html__( 'Square Webhook Issue', 'event-tickets' ),
			sprintf(
				/* translators: %s: List of webhook issues */
				esc_html__( 'The Square payment gateway has webhook issues: %s. This may affect payment updates and order processing.', 'event-tickets' ),
				$issues_text
			),
			esc_url( $settings_url ),
			esc_html__( 'Fix Webhook Configuration', 'event-tickets' ),
			esc_attr( $webhook_nonce )
		);
	}

	/**
	 * Check if the given screen ID is a Tickets admin page.
	 *
	 * @since TBD
	 *
	 * @param string $screen_id The screen ID to check.
	 *
	 * @return bool Whether this is a Tickets admin page.
	 */
	protected function is_tickets_admin_page( $screen_id ) {
		$valid_screens = [
			'tribe_events_page_tec-tickets-settings',
			'tribe_events_page_tec-tickets-commerce-orders',
			'tribe_events_page_tickets-commerce-orders',
			'events_page_tec-tickets-settings',
			'events_page_tec-tickets-commerce-orders',
			'events_page_tickets-commerce-orders',
		];

		return in_array( $screen_id, $valid_screens, true );
	}
}
