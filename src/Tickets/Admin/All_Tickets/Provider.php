<?php
/**
 * The main service provider for the Tickets Admin Attendees page.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Admin
 */

namespace TEC\Tickets\Admin\All_Tickets;

use TEC\Common\Contracts\Service_Provider;

/**
 * Service provider for the Tickets Admin All Tickets
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Admin
 */
class Provider extends Service_Provider {

	/**
	 * Register the provider singletons.
	 *
	 * @since TBD
	 */
	public function register() {
		$this->register_hooks();
		$this->register_assets();

		// Register the SP on the container.
		$this->container->register( static::class, $this );
	}

	/**
	 * Registers the provider handling all the 1st level filters and actions for the Tickets Admin area.
	 *
	 * @since TBD
	 */
	protected function register_hooks() {
		$hooks = new Hooks( $this->container );
		$hooks->register();

		// Allow Hooks to be removed, by having the them registered to the container.
		$this->container->singleton( Hooks::class, $hooks );
	}

	/**
	 * Registers the assets for the Tickets All Tickets area.
	 *
	 * @since TBD
	 */
	protected function register_assets() {
	}
}
