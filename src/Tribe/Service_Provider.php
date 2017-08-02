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
		$this->container->singleton( 'events-tickets.assets', new Tribe__Tickets__Assets() );

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
		add_action( 'wp_enqueue_scripts', tribe_callback( 'events-tickets.assets', 'enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', tribe_callback( 'events-tickets.assets', 'admin_enqueue_scripts' ) );
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
