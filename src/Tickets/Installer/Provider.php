<?php
/**
 * Event Tickets Installer Service Provider
 */

namespace TEC\Tickets\Installer;

use TEC\Common\StellarWP\Installer\Installer;
use TEC\Common\Contracts\Service_Provider;

/**
 * Class Provider
 *
 * @since 5.23.0
 *
 * @package TEC\Tickets\Installer
 */
class Provider extends Service_Provider {


	/**
	 * Binds and sets up implementations.
	 *
	 * Note: plugins need to be registered *early* and *before* they can be installed or activated by StellarWP\Installer.
	 * If they are not registered early enough, the installer ajax hooks *will not work*.
	 *
	 * @since 6.0.9
	 */
	public function register() {
		$this->container->singleton( static::class, $this );

		Installer::get()->register_plugin( 'the-events-calendar', 'The Events Calendar' );
	}
}
