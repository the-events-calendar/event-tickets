<?php

namespace TEC\Tickets\Commerce\Gateways;

/**
 * Class Gateways Manager.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\Gateways
 */
class Manager {
	/**
	 * The option name that holds the gateway for a specific ticket and attendee.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $option_gateway = '_tickets_commerce_gateway';

	/**
	 * Determine whether PayPal Legacy should be shown as an available gateway.
	 *
	 * @since TBD
	 *
	 * @return bool Whether PayPal Legacy should be shown as an available gateway.
	 */
	public function should_show_legacy() {
		/**
		 * Determine whether PayPal Legacy should be shown as an available gateway.
		 *
		 * @since TBD
		 *
		 * @param bool $should_show Whether PayPal Legacy should be shown as an available gateway.
		 */
		return (bool) apply_filters( 'tec_tickets_commerce_display_legacy', ! tec_tickets_commerce_is_enabled() );
	}

	/**
	 * Get the list of registered Tickets Commerce gateways.
	 *
	 * @since TBD
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
		 * @since TBD
		 *
		 * @param array $gateways The list of registered Tickets Commerce gateways.
		 */
		return (array) apply_filters( 'tec_tickets_commerce_gateways', [] );
	}

	/**
	 * Get the current Tickets Commerce gateway.
	 *
	 * @since TBD
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