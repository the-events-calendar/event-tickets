<?php

namespace Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Legacy;

use Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\Abstract_Gateway;
use Tribe__Tickets__Commerce__PayPal__Main as PayPal_Main;

/**
 * The PayPal Standard (Legacy) specific functionality.
 *
 * This class will contain everything we can pull out from Tribe__Tickets__Commerce__PayPal__Main that is
 * PayPal Standard (Legacy) specific. Anything we can do to split off the logic into this class would be helpful for
 * long term maintenance and reducing mess between the various Tickets Commerce gateways developed.
 *
 * @since   TBD
 * @package Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Legacy
 */
class Gateway extends Abstract_Gateway {

	/**
	 * The Gateway key.
	 *
	 * @since TBD
	 *
	 * @const
	 */
	const GATEWAY_KEY = 'paypal-legacy';

	/**
	 * Register the gateway for Tickets Commerce.
	 *
	 * @since TBD
	 *
	 * @param array       $gateways The list of registered Tickets Commerce gateways.
	 * @param PayPal_Main $commerce The Tickets Commerce provider.
	 *
	 * @return array The list of registered Tickets Commerce gateways.
	 */
	public function register_gateway( array $gateways, $commerce ) {
		if ( ! $this->should_show_paypal_legacy() ) {
			return $gateways;
		}

		$gateways['paypal-legacy'] = [
			'label' => __( 'PayPal Standard (Legacy)', 'event-tickets' ),
			'class' => self::class,
		];

		return $gateways;
	}

	/**
	 * Determine whether the provider is active depending on the gateway settings.
	 *
	 * @since TBD
	 *
	 * @param bool        $is_active Whether the provider is active.
	 * @param PayPal_Main $commerce  The Tickets Commerce provider.
	 *
	 * @return bool Whether the provider is active.
	 */
	public function is_active( $is_active, $commerce ) {
		// Bail if the provider is already showing as active.
		if ( $is_active ) {
			return $is_active;
		}

		// If this gateway shouldn't be shown, then don't change the active status.
		if ( ! $this->should_show( false, $commerce ) ) {
			return $is_active;
		}

		/** @var Tribe__Tickets__Commerce__PayPal__Gateway $gateway */
		$gateway = tribe( 'tickets.commerce.paypal.gateway' );

		/** @var Tribe__Tickets__Commerce__PayPal__Handler__Interface $handler */
		$handler = $gateway->build_handler();

		// Only mark as active if config status is complete.
		return 'complete' === $handler->get_config_status();
	}

	/**
	 * Determine whether the gateway should be shown as an available gateway.
	 *
	 * @since TBD
	 *
	 * @param bool        $should_show Whether the gateway should be shown as an available gateway.
	 * @param PayPal_Main $commerce    The Tickets Commerce provider.
	 *
	 * @return bool Whether the gateway should be shown as an available gateway.
	 */
	public function should_show( $should_show, $commerce ) {
		// Bail if it's been manually overridden to show.
		if ( $should_show ) {
			return $should_show;
		}

		// This site installed Event Tickets 5.2+ so it never should show the old option.
		if ( ! tribe_installed_before( 'Tribe__Tickets__Main', '5.2' ) ) {
			return false;
		}

		// @todo Future: Disable gateway if it does not match the current gateway as the next step down the road.
		/*if ( self::GATEWAY_KEY !== tribe_get_option( $commerce->option_gateway ) ) {
			return false;
		}*/

		// Tribe Commerce was previously enabled.
		$paypal_enable = tribe_is_truthy( tribe_get_option( 'ticket-paypal-enable' ) );

		// Tribe Commerce PayPal email was previously set.
		$paypal_email_is_set = '' !== tribe_get_option( 'ticket-paypal-email' );

		// The gateway should not be shown if it was never enabled or email was previously set.
		if ( ! $paypal_enable || ! $paypal_email_is_set ) {
			return false;
		}

		return true;
	}

}
