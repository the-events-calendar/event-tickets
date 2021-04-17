<?php

namespace Tribe\Tickets\Commerce\Tickets_Commerce\Gateways;

use Tribe__Tickets__Commerce__PayPal__Main as PayPal_Main;

/**
 * The gateway settings related functionality.
 *
 * @since   TBD
 * @package Tribe\Tickets\Commerce\Tickets_Commerce\Gateways
 *
 */
abstract class Abstract_Settings {

	/**
	 * Get the list of admin settings for the gateway.
	 *
	 * @since TBD
	 *
	 * @return array The list of admin settings for the gateway.
	 */
	abstract public function get_admin_settings( );

}
