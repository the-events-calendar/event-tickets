<?php
/**
 * The main service provider for the Tickets Admin Attendees page.
 *
 * @since   5.9.1
 * @package TEC\Tickets\Admin
 */

namespace TEC\Tickets\Admin\Attendees;

/**
 * Service provider for the Tickets Admin Attendees
 *
 * @since   5.9.1
 * @package TEC\Tickets\Admin
 */
class Provider extends \tad_DI52_ServiceProvider {

	/**
	 * Register the provider singletons.
	 *
	 * @since 5.9.1
	 */
	public function register() {
		if (
			! tribe( 'tickets.attendees' )->user_can_manage_attendees()
			|| ! tec_tickets_attendees_page_is_enabled()
		) {
			return;
		}

		$this->register_hooks();
		$this->register_assets();

		// Register the SP on the container.
		$this->container->singleton( static::class, $this );
	}

	/**
	 * Registers the provider handling all the 1st level filters and actions for the Tickets Admin area.
	 *
	 * @since 5.9.1
	 */
	protected function register_hooks() {
		$hooks = new Hooks( $this->container );
		$hooks->register();

		// Allow Hooks to be removed, by having the them registered to the container.
		$this->container->singleton( Hooks::class, $hooks );
	}

	/**
	 * Registers the assets for the Tickets Attendees area.
	 *
	 * @since 5.10.0
	 */
	protected function register_assets() {
		$assets = new Assets( $this->container );
		$assets->register();

		$this->container->singleton( Assets::class, $assets );
	}
}
