<?php

namespace TEC\Tickets\Commerce\Gateways;

/**
 * Class Gateways Manager.
 *
 * @since   5.1.6
 *
 * @package TEC\Tickets\Commerce\Gateways
 */
class Manager {
	/**
	 * The option name that holds the gateway for a specific ticket and attendee.
	 *
	 * @since 5.1.6
	 *
	 * @var string
	 */
	public static $option_gateway = '_tickets_commerce_gateway';

	/**
	 * Determine whether PayPal Legacy should be shown as an available gateway.
	 *
	 * @since 5.1.6
	 *
	 * @return bool Whether PayPal Legacy should be shown as an available gateway.
	 */
	public function should_show_legacy() {
		/**
		 * Determine whether PayPal Legacy should be shown as an available gateway.
		 *
		 * @since 5.1.6
		 *
		 * @param bool $should_show Whether PayPal Legacy should be shown as an available gateway.
		 */
		return (bool) apply_filters( 'tec_tickets_commerce_display_legacy', ! tec_tickets_commerce_is_enabled() );
	}

	/**
	 * Get the list of registered Tickets Commerce gateways.
	 *
	 * @since 5.1.6
	 *
	 * @return array The list of registered Tickets Commerce gateways.
	 */
	public function get_gateways() {
		/**
		 * Allow filtering the list of registered Tickets Commerce gateways.
		 *
		 * PayPal Commerce filters at priority 10.
		 * PayPal Legacy filters at priority 15.
		 *
		 * @since 5.1.6
		 *
		 * @param array $gateways The list of registered Tickets Commerce gateways.
		 */
		return (array) apply_filters( 'tec_tickets_commerce_gateways', [] );
	}

	/**
	 * Get the current Tickets Commerce gateway.
	 *
	 * @since 5.1.6
	 *
	 * @return string The current Tickets Commerce gateway.
	 */
	public function get_current_gateway() {
		$default = Legacy\Gateway::get_key();

		if ( ! $this->should_show_legacy() ) {
			$default = PayPal\Gateway::get_key();
		}

		return (string) tribe_get_option( static::$option_gateway, $default );
	}
}