<?php
/**
 * The main service provider for the Tickets Emails.
 *
 * @since   TBD
 * @package TEC\Tickets\Emails
 */

namespace TEC\Tickets\Emails;

use tad_DI52_ServiceProvider;
use TEC\Tickets\Commerce\Gateways;
use \Tribe__Tickets__Main as Tickets_Plugin;


/**
 * Service provider for the Tickets Commerce.
 *
 * @since   5.1.6
 * @package TEC\Tickets\Emails
 */
class Provider extends tad_DI52_ServiceProvider {

	/**
	 * Register the provider singletons.
	 *
	 * @since 5.1.6
	 */
	public function register() {

		$this->container->register( Emails_Tab::class );
		$this->container->register( Settings::class );

		$this->register_assets();
		$this->register_hooks();


		// Register the SP on the container.
		$this->container->singleton( static::class, $this );
		$this->container->singleton( 'tickets.emails.provider', $this );
	}

	/**
	 * Registers the provider handling all the 1st level filters and actions for Tickets Emails.
	 *
	 * @since TBD
	 */
	protected function register_assets() {
		$assets = new Assets( $this->container );
		$assets->register();

		$this->container->singleton( Assets::class, $assets );
	}

	/**
	 * Registers the provider handling all the 1st level filters and actions for Tickets Emails.
	 *
	 * @since TBD
	 */
	protected function register_hooks() {
		$hooks = new Hooks( $this->container );
		$hooks->register();

		// Allow Hooks to be removed, by having the them registered to the container
		$this->container->singleton( Hooks::class, $hooks );
		$this->container->singleton( 'tickets.emails.hooks', $hooks );
	}
}
