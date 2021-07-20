<?php
/**
 *
 * @todo This file is not being used currently but we need to remove this before we launch Tickets Commerce.
 *
 * @since 5.1.6
 *
 * @package TEC\Tickets\Commerce\Gateways\Legacy
 */

namespace TEC\Tickets\Commerce\Gateways\Legacy;

use TEC\Tickets\Commerce\Gateways\Abstract_Gateway;
use TEC\Tickets\Commerce\Gateways\Manager;
use Tribe__Tickets__Commerce__PayPal__Main as PayPal_Main;

/**
 * The PayPal Standard (Legacy) specific functionality.
 *
 * This class will contain everything we can pull out from Tribe__Tickets__Commerce__PayPal__Main that is
 * PayPal Standard (Legacy) specific. Anything we can do to split off the logic into this class would be helpful for
 * long term maintenance and reducing mess between the various Tickets Commerce gateways developed.
 *
 * @since   5.1.6
 * @package TEC\Tickets\Commerce\Gateways\PayPal_Legacy
 */
class Gateway extends Abstract_Gateway {

	/**
	 * @inheritDoc
	 */
	protected static $key = 'paypal-legacy';

	/**
	 * @inheritDoc
	 */
	public static function get_label() {
		return __( 'PayPal Standard (Legacy)', 'event-tickets' );
	}

	/**
	 * @inheritDoc
	 */
	public static function is_active() {
		// If this gateway shouldn't be shown, then don't change the active status.
		if ( ! static::should_show() ) {
			return false;
		}

		/** @var \Tribe__Tickets__Commerce__PayPal__Gateway $gateway */
		$gateway = tribe( 'tickets.commerce.paypal.gateway' );

		/** @var \Tribe__Tickets__Commerce__PayPal__Handler__Interface $handler */
		$handler = $gateway->build_handler();

		// Only mark as active if config status is complete.
		return 'complete' === $handler->get_config_status();
	}

	/**
	 * @inheritDoc
	 */
	public static function should_show() {
		/**
		 * @todo for we just dont show legacy for testing purposes.
		 */
		if ( ! tribe( Manager::class )->should_show_legacy() ) {
			return false;
		}

		// This site installed Event Tickets 5.2+ so it never should show the old option.
		if ( ! tribe_installed_before( 'Tribe__Tickets__Main', '5.2' ) ) {
			return false;
		}

		// @todo Future: Disable gateway if it does not match the current gateway as the next step down the road.
		/*if ( $this->gateway_key !== tribe_get_option( $commerce->option_gateway ) ) {
			return false;
		}*/

		// Tribe Commerce was previously enabled.
		$paypal_enable = tribe_is_truthy( tribe_get_option( 'ticket-paypal-enable' ) );

		// Tribe Commerce PayPal email was previously set.
		$paypal_email_is_set = '' !== tribe_get_option( 'ticket-paypal-email' );

		// The gateway should show if it was ever enabled or email was previously set.
		if ( $paypal_enable || $paypal_email_is_set ) {
			return true;
		}

		// Default this gateway to off.
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function get_settings() {
		/** @var Settings $settings */
		$settings = tribe( Settings::class );

		return $settings->get_settings();
	}

}
