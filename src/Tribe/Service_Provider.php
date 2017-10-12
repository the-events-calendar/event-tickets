<?php
/**
 * Class Tribe__Tickets_Plus__Service_Provider
 *
 * Provides the Events Tickets Plus service.
 *
 * This class should handle implementation binding, builder functions and hooking for any first-level hook and be
 * devoid of business logic.
 *
 * @since TBD
 */
class Tribe__Tickets__Service_Provider extends tad_DI52_ServiceProvider {
	/**
	 * Binds and sets up implementations.
	 *
	 * @since TBD
	 */
	public function register() {
		$this->container->singleton( 'tickets.assets', new Tribe__Tickets__Assets() );
		$this->container->singleton( 'tickets.handler', 'Tribe__Tickets__Tickets_Handler' );

		// Caching
		$this->container->singleton( 'tickets.cache-central', 'Tribe__Tickets__Cache__Central', array( 'hook' ) );
		$this->container->singleton( 'tickets.cache', tribe( 'tickets.cache-central' )->get_cache() );

		// Query Vars
		$this->container->singleton( 'tickets.query', 'Tribe__Tickets__Query', array( 'hook' ) );

		// Tribe Data API Init
		$this->container->singleton( 'tickets.data_api', 'Tribe__Tickets__Data_API' );

		// View links, columns and screen options
		$this->container->singleton( 'tickets.admin.views', 'Tribe__Tickets__Admin__Views', array( 'hook' ) );
		$this->container->singleton( 'tickets.admin.columns', 'Tribe__Tickets__Admin__Columns', array( 'hook' ) );
		$this->container->singleton( 'tickets.admin.screen-options', 'Tribe__Tickets__Admin__Screen_Options', array( 'hook' ) );

		$this->hook();
	}

	/**
	 * Any hooking for any class needs happen here.
	 *
	 * In place of delegating the hooking responsibility to the single classes they are all hooked here.
	 *
	 * @since TBD
	 */
	protected function hook() {
		tribe( 'tickets.query' );
		tribe( 'tickets.handler' );

		tribe( 'tickets.assets' )->enqueue_scripts();
		tribe( 'tickets.assets' )->admin_enqueue_scripts();

		if ( is_admin() ) {
			tribe( 'tickets.admin.views' );
			tribe( 'tickets.admin.columns' );
			tribe( 'tickets.admin.screen-options' );
		}
	}

	/**
	 * Binds and sets up implementations at boot time.
	 *
	 * @since TBD
	 */
	public function boot() {
		// no ops
	}
}
