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
	 * Determines if the webhook notice should be displayed.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function should_display_notice() {
		// Only show on admin pages.
		if ( ! is_admin() ) {
			return false;
		}

		// If Square gateway is not enabled, don't show the notice.
		if ( ! tribe( Gateway::class )->is_enabled() ) {
			return false;
		}

		// If Square gateway is not enabled, don't show the notice.
		if ( ! tribe( Gateway::class )->is_active() ) {
			return false;
		}

		// Don't show on tickets admin pages where we already have inline notices.
		$screen = get_current_screen();
		if ( $screen && $this->is_tickets_admin_page( $screen->id ) ) {
			return false;
		}

		if ( ! tribe( Webhooks::class )->is_webhook_healthy() ) {
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
		$webhook_id = tribe( Webhooks::class )->get_webhook_id();
		$webhooks   = tribe( Webhooks::class );

		$issues = [];

		// Check if webhook is missing.
		if ( empty( $webhook_id ) ) {
			$issues[] = esc_html__( 'Webhook not registered', 'event-tickets' );
		} else {
			// All other checks are only relevant if the webhook is registered.
			// API version issues.
			if ( ! $webhooks->is_api_version_current() ) {
				$issues[] = esc_html__( 'API version mismatch', 'event-tickets' );
			}

			// Event type issues.
			if ( ! $webhooks->is_event_types_current() ) {
				$issues[] = esc_html__( 'Event types configuration outdated', 'event-tickets' );
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
