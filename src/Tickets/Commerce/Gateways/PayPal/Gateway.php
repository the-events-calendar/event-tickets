<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal;

use TEC\Tickets\Commerce\Gateways\Abstract_Gateway;

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
		return __( 'PayPal Commerce', 'event-tickets' );
	}

	/**
	 * @inheritDoc
	 */
	public static function is_active() {
		// If this gateway shouldn't be shown, then don't change the active status.
		if ( ! static::should_show() ) {
			return false;
		}

		return tribe( Merchant::class )->is_active();
	}

	/**
	 * Get the list of settings for the gateway.
	 *
	 * @since 5.1.6
	 *
	 * @return array The list of settings for the gateway.
	 */
	public function get_settings() {
		return tribe( Settings::class )->get_settings();
	}

	/**
	 * Determine whether Tickets Commerce is in test mode.
	 *
	 * @since 5.1.6
	 *
	 * @return bool Whether Tickets Commerce is in test mode.
	 */
	public static function is_test_mode() {
		return tribe_is_truthy( tribe_get_option( \TEC\Tickets\Commerce\Settings::$option_sandbox ) );
	}
}
