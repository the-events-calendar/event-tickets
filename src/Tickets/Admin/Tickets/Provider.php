<?php
/**
 * The main service provider for the Tickets Admin Attendees page.
 *
 * @since 5.14.0
 *
 * @package TEC\Tickets\Admin
 */

namespace TEC\Tickets\Admin\Tickets;

use TEC\Common\Contracts\Service_Provider;

/**
 * Service provider for the Tickets Admin All Tickets
 *
 * @since 5.14.0
 *
 * @package TEC\Tickets\Admin
 */
class Provider extends Service_Provider {

	/**
	 * Register the provider singletons.
	 *
	 * @since 5.14.0
	 */
	public function register() {
		$this->register_hooks();
		$this->register_assets();
	}

	/**
	 * Registers the provider handling all the 1st level filters and actions for the Tickets Admin area.
	 *
	 * @since 5.14.0
	 */
	protected function register_hooks() {
		$hooks = new Hooks( $this->container );
		$hooks->register();

		// Register singletons.
		$this->container->singleton( Hooks::class, $hooks );
		$this->container->singleton( List_Table::class, List_Table::class );
		$this->container->singleton( Screen_Options::class, Screen_Options::class );
		$this->container->singleton( Page::class, Page::class );
	}

	/**
	 * Registers the assets for the Tickets All Tickets area.
	 *
	 * @since 5.14.0
	 */
	protected function register_assets() {
		$this->container->register( Assets::class );
	}
}
