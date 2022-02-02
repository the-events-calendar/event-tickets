<?php
/**
 *
 * @since   5.1.6
 *
 * @package TEC\Tickets\Commerce
 */

namespace TEC\Tickets\Commerce\Gateways\Contracts;

/**
 * Abstract Settings
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Contracts
 */
abstract class Abstract_Settings {

	/**
	 * The option key for the gateway-specific sandbox.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $option_sandbox;

	/**
	 * Get the list of settings for the gateway.
	 *
	 * @since 5.1.6
	 *
	 * @return array The list of settings for the gateway.
	 */
	abstract public function get_settings();

	/**
	 * Get the HTML for the connection box in the admin
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	abstract function get_connection_settings_html();

	/**
	 * Check if this gateway is currently in test mode.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function is_gateway_test_mode() {
		return tribe_is_truthy( tribe_get_option( static::$option_sandbox ) );
	}
}
