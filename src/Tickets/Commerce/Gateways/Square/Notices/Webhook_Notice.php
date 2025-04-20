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
	 * Determines if the webhook notice should be displayed
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function should_display_notice() {
		// If Square gateway is not enabled, don't show the notice
		if ( ! tribe( Gateway::class )->is_enabled() ) {
			return false;
		}

		// Get the webhook health status
		$status = tribe_get_option( Webhooks::$option_webhook_last_check, [] );

		// If there's no status or webhooks are healthy, don't show notice
		if ( empty( $status ) || ( isset( $status['is_healthy'] ) && $status['is_healthy'] ) ) {
			return false;
		}

		// Only show on admin pages
		if ( ! is_admin() ) {
			return false;
		}

		return true;
	}

	/**
	 * Render the webhook notice
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function render_notice() {
		$message = sprintf(
			'<p><strong>%1$s</strong></p><p>%2$s</p>',
			esc_html__( 'Square Webhook Issue', 'event-tickets' ),
			esc_html__( 'The Square payment gateway webhooks are not functioning correctly. This may affect payment updates and order processing. Please check your Square settings or contact support.', 'event-tickets' )
		);

		return $message;
	}
}
