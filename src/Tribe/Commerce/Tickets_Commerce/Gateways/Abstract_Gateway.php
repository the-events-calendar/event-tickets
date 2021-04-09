<?php

namespace Tribe\Tickets\Commerce\Tickets_Commerce\Gateways;

use Tribe__Tickets__Commerce__PayPal__Main as PayPal_Main;

/**
 * The gateway related functionality.
 *
 * @since   TBD
 * @package Tribe\Tickets\Commerce\Tickets_Commerce\Gateways
 *
 */
abstract class Abstract_Gateway {

	/**
	 * The Gateway key.
	 *
	 * @since TBD
	 *
	 * @const
	 */
	const GATEWAY_KEY = '';

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
	abstract public function register_gateway( array $gateways, $commerce );

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
	abstract public function is_active( $is_active, $commerce );

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
		return true;
	}

}
