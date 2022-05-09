<?php
/**
 * The main service provider for the Tickets Admin area.
 *
 * @since   5.3.4
 * @package TEC\Tickets\Admin
 */

namespace TEC\Tickets\Admin;


/**
 * Service provider for the Tickets Admin area.
 *
 * @since   5.3.4
 * @package TEC\Tickets\Admin
 */
class Provider extends \tad_DI52_ServiceProvider {

	/**
	 * Register the provider singletons.
	 *
	 * @since 5.3.4
	 */
	public function register() {

		$this->register_hooks();

		// Register the SP on the container.
		$this->container->singleton( static::class, $this );
		$this->container->singleton( 'tickets.admin.provider', $this );

		// Register singleton classes.
		$this->container->singleton( Upsell::class );

	}

	/**
	 * Registers the provider handling all the 1st level filters and actions for the Tickets Admin area.
	 *
	 * @since 5.3.4
	 */
	protected function register_hooks() {
		$hooks = new Hooks( $this->container );
		$hooks->register();

		// Allow Hooks to be removed, by having the them registered to the container
		$this->container->singleton( Hooks::class, $hooks );
		$this->container->singleton( 'tickets.admin.hooks', $hooks );
	}

}
