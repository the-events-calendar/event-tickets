<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal;

use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Gateway;
use TEC\Tickets\Commerce\Notice_Handler;
use \Tribe__Tickets__Main;

/**
 * Class Gateway
 *
 * @since   5.1.6
 * @package TEC\Tickets\Commerce\Gateways\PayPal
 */
class Gateway extends Abstract_Gateway {
	/**
	 * @inheritDoc
	 */
	protected static $key = 'paypal';

	/**
	 * @inheritDoc
	 */
	protected static $settings = Settings::class;

	/**
	 * @inheritDoc
	 */
	protected static $merchant = Merchant::class;

	/**
	 * PayPal attribution ID for requests.
	 *
	 * @since 5.1.6
	 *
	 * @const
	 */
	const ATTRIBUTION_ID = 'TheEventsCalendar_SP_PPCP';

	/**
	 * PayPal tracking ID version.
	 *
	 * This shouldn't be updated unless we are modifying something on the PayPal user level.
	 *
	 * @since 5.1.9
	 *
	 * @var string
	 */
	const VERSION = '1.0.0';

	/**
	 * @inheritDoc
	 */
	public static function get_label() {
		return __( 'PayPal', 'event-tickets' );
	}

	/**
	 * @inheritDoc
	 */
	public function get_admin_notices() {
		$notices = [
			[
				'slug'     => 'tc-paypal-signup-complete',
				'content'  => __( 'PayPal is now connected.', 'event-tickets' ),
				'type'     => 'info',
			],
			[
				'slug'     => 'tc-paypal-disconnect-failed',
				'content'  => __( 'Failed to disconnect PayPal account.', 'event-tickets' ),
				'type'     => 'error',
			],
			[
				'slug'     => 'tc-paypal-disconnected',
				'content'  => __( 'Disconnected PayPal account.', 'event-tickets' ),
				'type'     => 'info',
			],
			[
				'slug'     => 'tc-paypal-refresh-token-failed',
				'content'  => __( 'Failed to refresh PayPal access token.', 'event-tickets' ),
				'type'     => 'error',
			],
			[
				'slug'     => 'tc-paypal-refresh-token',
				'content'  => __( 'PayPal access token was refreshed successfully.', 'event-tickets' ),
				'type'     => 'info',
			],
			[
				'slug'     => 'tc-paypal-refresh-user-info-failed',
				'content'  => __( 'Failed to refresh PayPal user info.', 'event-tickets' ),
				'type'     => 'error',
			],
			[
				'slug'     => 'tc-paypal-refresh-user-info',
				'content'  => __( 'PayPal user info was refreshed successfully.', 'event-tickets' ),
				'type'     => 'info',
			],
			[
				'slug'     => 'tc-paypal-refresh-webhook-failed',
				'content'  => __( 'Failed to refresh PayPal webhooks.', 'event-tickets' ),
				'type'     => 'error',
			],
			[
				'slug'     => 'tc-paypal-refresh-webhook-success',
				'content'  => __( 'PayPal webhooks refreshed successfully.', 'event-tickets' ),
				'type'     => 'info',
			],
			[
				'slug'     => 'tc-paypal-ssl-not-available',
				'content'  => __( 'A valid SSL certificate is required to set up your PayPal account and accept payments', 'event-tickets' ),
				'type'     => 'error',
			],
		];

		 return $notices;
	}

	/**
	 * @inheritDoc
	 */
	public function get_logo_url() {
		return Tribe__Tickets__Main::instance()->plugin_url . 'src/resources/images/admin/paypal_logo.png';
	}

	/**
	 * @inheritDoc
	 */
	public function get_subtitle() {
		return __( 'Enable payments through PayPal, Venmo, and credit card', 'event-tickets' );
	}
	public static function is_enabled() {
		if ( ! static::should_show() ) {
			return false;
		}
		
		$option_value = tribe_get_option( static::get_enabled_option_key() );
		if ( '' !== $option_value ) {
			return (bool) $option_value;
		}
		
		// If option is not explicitly set, the default will be if PayPal is connected.
		return self::is_connected();
	}
	
	/**
	 * @inheritDoc
	 */
	public static function get_checkout_template_vars() {		
		return tribe( Buttons::class )->get_checkout_template_vars();
	}
}
