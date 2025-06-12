<?php
/**
 * File: Provider.php
 *
 * Handles the configuration provider for Event Tickets.
 * This file manages the registration and unregistration of configuration services
 * for the Event Tickets plugin.
 *
 * @package TEC\Tickets\Configuration
 */

namespace TEC\Tickets\Configuration;

use TEC\Common\Configuration\Configuration;
use TEC\Common\Configuration\Configuration_Loader;
use TEC\Common\Configuration\Constants_Provider;
use TEC\Common\Contracts\Service_Provider;

/**
 * Class Provider
 *
 * Service provider for handling configuration in Event Tickets.
 * Manages the registration and unregistration of configuration services,
 * including the Constants Provider for configuration loading.
 *
 * @since 5.24.0
 */
class Provider extends Service_Provider {

	/**
	 * Registers Configuration provider.
	 *
	 * @since 5.24.0
	 */
	public function register(): void {
		tribe( Configuration_Loader::class )->add( new Constants_Provider() );
	}

	/**
	 * Removes provider.
	 *
	 * @since 5.24.0
	 */
	public function unregister(): void {
		tribe()->offsetUnset( Configuration_Loader::class );
		tribe()->offsetUnset( Configuration::class );
	}
}
