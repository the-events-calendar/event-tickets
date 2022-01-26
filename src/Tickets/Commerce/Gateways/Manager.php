<?php

namespace TEC\Tickets\Commerce\Gateways;

use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Gateway;
use TEC\Tickets\Settings;

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
	 * The option name prefix that configured whether or not a gateway is enabled. 
	 * It is followed by the gateway 'key'
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $option_gateway_enabled_prefix = '_tickets_commerce_gateway_enabled_';

	/**
	 * Get the list of registered Tickets Commerce gateways.
	 *
	 * @since 5.1.6
	 *
	 * @return Abstract_Gateway[] The list of registered Tickets Commerce gateways.
	 */
	public function get_gateways() {
		/**
		 * Allow filtering the list of registered Tickets Commerce gateways.
		 *
		 * PayPal Commerce filters at priority 10.
		 *
		 * @since 5.1.6
		 *
		 * @param Abstract_Gateway[] $gateways The list of registered Tickets Commerce gateways.
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
		$default = PayPal\Gateway::get_key();

		return (string) tribe_get_option( static::$option_gateway, $default );
	}

	/**
	 * Get the gateway settings from all gateways.
	 *
	 * @since 5.1.9
	 *
	 * @return array[]
	 */
	public function get_gateway_settings() {
		$gateways = $this->get_gateways();

		$gateway_setting_groups = [];

		// Get all of the gateway settings.
		foreach ( $gateways as $gateway_key => $gateway ) {
			if ( ! $gateway::should_show() ) {
				continue;
			}

			// Get the gateway settings.
			$gateway_settings = $gateway->get_settings();

			// If there are no gateway settings, don't show this section at all.
			if ( empty( $gateway_settings ) ) {
				continue;
			}

			$gateway_setting_groups[] = $gateway_settings;
		}

		return array_merge( ...$gateway_setting_groups );
	}

	/**
	 * Get gateway by key.
	 *
	 * @since 5.2.0
	 *
	 * @param string $key Key for expected gateway.
	 *
	 * @return Abstract_Gateway
	 */
	public function get_gateway_by_key( $key ) {
		if ( empty( $key ) ) {
			return;
		}
		
		$gateways = $this->get_gateways();
		if ( ! isset( $gateways[ $key ] ) ) {
			return;
		}

		return $gateways[ $key ];
	}

	/**
	 * Get gateway enabled option by key.
	 *
	 * @since TBD
	 *
	 * @param Abstract_Gateway|string $gateway Key or Gateway object for gateway.
	 *
	 * @return string
	 */
	public static function get_enabled_option_by_key( $gateway ) {
		$key = $gateway instanceof Abstract_Gateway ? $gateway->get_key() : $gateway;

		return self::$option_gateway_enabled_prefix . $key;
	}

	/**
	 * Return if gateway is enabled.
	 *
	 * @since TBD
	 *
	 * @param Abstract_Gateway|string $gateway Key or Gateway object for gateway.
	 *
	 * @return boolean 
	 */
	public function is_gateway_enabled( $gateway ) {
		$key = $gateway instanceof Abstract_Gateway ? $gateway->get_key() : $gateway;

		return (bool) tribe_get_option( $this->get_enabled_option_by_key( $key ) );
	}
}
