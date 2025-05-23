<?php
/**
 * Square Notices Controller
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Notices
 */

namespace TEC\Tickets\Commerce\Gateways\Square;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\Contracts\Container;

/**
 * Class Controller
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Notices
 */
class Notices_Controller extends Controller_Contract {
	/**
	 * Webhook notice slug.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const WEBHOOK_NOTICE_SLUG = 'tec-tickets-commerce-square-webhook-notice';

	/**
	 * Not ready to sell notice slug.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const NOT_READY_TO_SELL_NOTICE_SLUG = 'tec-tickets-commerce-square-not-ready-to-sell-notice';

	/**
	 * Webhooks instance.
	 *
	 * @since TBD
	 *
	 * @var Webhooks
	 */
	private Webhooks $webhooks;

	/**
	 * Gateway instance.
	 *
	 * @since TBD
	 *
	 * @var Gateway
	 */
	private Gateway $gateway;

	/**
	 * Merchant instance.
	 *
	 * @since TBD
	 *
	 * @var Merchant
	 */
	private Merchant $merchant;

	/**
	 * Constructor.
	 *
	 * @since TBD
	 *
	 * @param Container $container Container instance.
	 * @param Webhooks  $webhooks  Webhooks instance.
	 * @param Gateway   $gateway   Gateway instance.
	 * @param Merchant  $merchant  Merchant instance.
	 */
	public function __construct( Container $container, Webhooks $webhooks, Gateway $gateway, Merchant $merchant ) {
		parent::__construct( $container );
		$this->webhooks = $webhooks;
		$this->gateway  = $gateway;
		$this->merchant = $merchant;
	}

	/**
	 * Register the notice providers.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function do_register(): void {
		tribe_notice(
			self::WEBHOOK_NOTICE_SLUG,
			[ $this, 'render_webhook_notice' ],
			[
				'type'     => 'error',
				'dismiss'  => false,
				'priority' => 10,
			],
			[ $this, 'should_display_webhook_notice' ]
		);

		tribe_notice(
			self::NOT_READY_TO_SELL_NOTICE_SLUG,
			[ $this, 'render_not_ready_to_sell_notice' ],
			[
				'type'     => 'error',
				'dismiss'  => false,
				'priority' => 10,
			],
			[ $this, 'should_display_not_ready_to_sell_notice' ]
		);
	}

	/**
	 * Unregisters the notice providers.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {}

	/**
	 * Determines if the webhook notice should be displayed.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function should_display_webhook_notice() {
		// Only show on admin pages.
		if ( ! is_admin() ) {
			return false;
		}

		// If Square gateway is not enabled, don't show the notice.
		if ( ! $this->gateway->is_enabled() ) {
			return false;
		}

		// If Square gateway is not enabled, don't show the notice.
		if ( ! $this->gateway->is_active() ) {
			return false;
		}

		// Don't show on tickets admin pages where we already have inline notices.
		$screen = get_current_screen();
		if ( $screen && ! $this->is_tickets_admin_page( $screen->id ) ) {
			return false;
		}

		if ( $this->webhooks->is_webhook_expired() ) {
			return true;
		}

		return ! $this->webhooks->is_webhook_healthy();
	}

	/**
	 * Determines if the not ready to sell notice should be displayed.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function should_display_not_ready_to_sell_notice() {
		if ( ! is_admin() ) {
			return false;
		}

		if ( ! $this->gateway->is_enabled() ) {
			return false;
		}

		if ( ! $this->gateway->is_active() ) {
			return false;
		}

		return ! $this->merchant->is_ready_to_sell();
	}

	/**
	 * Render the webhook notice.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function render_webhook_notice() {
		$webhook_id = $this->webhooks->get_webhook_id();

		$issues = [];

		// Check if webhook is missing.
		if ( empty( $webhook_id ) ) {
			$issues[] = esc_html__( 'Webhook not registered', 'event-tickets' );
		} elseif ( $this->webhooks->is_webhook_expired() ) {
			$issues[] = esc_html__( 'Webhook expired', 'event-tickets' );
		} else {
			$issues[] = esc_html__( 'Bad webhook configuration', 'event-tickets' );
		}

		// If there are no issues, don't show the notice.
		if ( empty( $issues ) ) {
			return '';
		}

		$issues_text = implode( ', ', $issues );

		// Create a URL to the ticket settings page.
		$settings_url = admin_url( 'admin.php?page=tec-tickets-settings&tab=square' );

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
	 * Render the webhook notice.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function render_not_ready_to_sell_notice() {
		return sprintf(
			'<p><strong>%1$s</strong></p><p>%2$s</p><p><a href="%3$s" class="button button-primary">%4$s</a></p>',
			esc_html__( 'Square Location not configured', 'event-tickets' ),
			esc_html__( 'The Square payment gateway is not ready to sell until you configure a Business Location. .', 'event-tickets' ),
			esc_url( admin_url( 'admin.php?page=tec-tickets-settings&tab=square' ) ),
			esc_html__( 'Configure Business Location', 'event-tickets' )
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
			'tickets_page_tec-tickets-settings',
			'tickets_page_tec-tickets-commerce-orders',
			'tickets_page_tickets-commerce-orders',
		];

		return in_array( $screen_id, $valid_screens, true );
	}
}
