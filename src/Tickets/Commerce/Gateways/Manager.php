<?php

namespace TEC\Tickets\Commerce\Gateways;

use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Gateway;

/**
 * Class Gateways Manager.
 *
 * @since 5.1.6
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
	 * Get the list of registered Tickets Commerce gateways.
	 *
	 * @since 5.1.6
	 * @since 5.24.0 Return an array with the gateway key as the key and the gateway as the value.
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
		$gateways = (array) apply_filters( 'tec_tickets_commerce_gateways', [] );

		return array_combine( array_map( fn( Abstract_Gateway $gateway ) => $gateway->get_key(), $gateways ), $gateways );
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
	 * Get the available gateways.
	 *
	 * @since 5.24.0
	 *
	 * @param string $context The context in which the gateways are being retrieved.
	 *
	 * @return Abstract_Gateway[] The available gateways.
	 */
	public function get_available_gateways( string $context = 'checkout' ): array {
		$available_gateways = array_filter(
			$this->get_gateways(),
			fn( Abstract_Gateway $gateway ) => $gateway::is_enabled() && $gateway::is_active()
		);

		$available_gateways_in_context = [];

		switch ( $context ) {
			case 'checkout':
				foreach ( $available_gateways as $gateway ) {
					if ( $gateway->renders_solo() && ! empty( $available_gateways_in_context ) ) {
						continue;
					}

					$available_gateways_in_context[ $gateway->get_key() ] = $gateway;
				}
				break;
			default:
				$available_gateways_in_context = $available_gateways;
				break;
		}

		/**
		 * Allow filtering the available gateways in the context.
		 *
		 * @since 5.24.0
		 *
		 * @param Abstract_Gateway[] $available_gateways_in_context The available gateways in the context.
		 * @param Abstract_Gateway[] $available_gateways            The available gateways.
		 * @param string             $context                       The context in which the gateways are being retrieved.
		 */
		return (array) apply_filters(
			'tec_tickets_commerce_available_gateways',
			$available_gateways_in_context,
			$available_gateways,
			$context
		);
	}
}
