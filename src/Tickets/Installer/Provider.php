<?php
/**
 * The Events Calendar Installer Service Provider
 */

namespace TEC\Tickets\Installer;

use TEC\Common\StellarWP\Installer\Installer;
use TEC\Common\Contracts\Service_Provider;


class Provider extends Service_Provider {


	/**
	 * Binds and sets up implementations.
	 *
	 * @since TBD
	 */
	public function register() {
		$this->container->singleton( static::class, $this );

		Installer::get()->register_plugin( 'the-events-calendar', 'The Events Calendar' );
	}
}
