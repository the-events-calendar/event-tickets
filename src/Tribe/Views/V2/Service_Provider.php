<?php
namespace Tribe\Events\Tickets\Views\V2;

/**
 * The main service provider for Event Tickets support and additions to the Views V2 functions.
 *
 * @since   TBD
 * @package Tribe\Events\Tickets\Views\V2
 */
class Service_Provider extends \tad_DI52_ServiceProvider {

	/**
	 * Binds and sets up implementations.
	 *
	 * @since 4.10.9
	 */
	public function register() {

		require_once tribe( 'tickets.main' )->plugin_path . 'src/functions/views/provider.php';

		if ( ! tribe_events_tickets_views_v2_is_enabled() ) {
			return;
		}

		$this->register_hooks();

		// Register the SP on the container
		$this->container->singleton( 'tickets.views.v2.provider', $this );
		$this->container->singleton( static::class, $this );
	}

	/**
	 * Registers the provider handling all the 1st level filters and actions for Views v2.
	 *
	 * @since 4.10.9
	 */
	protected function register_hooks() {
		$hooks = new Hooks( $this->container );
		$hooks->register();

		// Allow Hooks to be removed, by having the them registred to the container
		$this->container->singleton( Hooks::class, $hooks );
		$this->container->singleton( 'tickets.views.v2.hooks', $hooks );
	}


}
