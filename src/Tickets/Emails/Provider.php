<?php
/**
 * The main service provider for the Tickets Emails.
 *
 * @since   TBD
 * @package TEC\Tickets\Emails
 */

namespace TEC\Tickets\Emails;

use tad_DI52_ServiceProvider;

/**
 * Service provider for the Tickets Emails.
 *
 * @since   TBD
 * @package TEC\Tickets\Emails
 */
class Provider extends tad_DI52_ServiceProvider {

	/**
	 * Register the provider singletons.
	 *
	 * @since TBD
	 */
	public function register() {

		// If not enabled, do not load Tickets Emails system.
		if ( ! tec_tickets_emails_is_enabled() ) {
			return;
		}

		$this->register_assets();
		$this->register_hooks();

		// Register singletons.
		$this->container->singleton( static::class, $this );
		$this->container->singleton( 'tickets.emails.provider', $this );
		$this->container->singleton( Emails_Tab::class );
		$this->container->singleton( 'tickets.emails.emails-tab', $this );

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
		$this->container->singleton( 'tickets.emails.assets', $assets );
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
