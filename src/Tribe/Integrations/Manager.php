<?php

/**
 * Class Tribe__Tickets__Integrations__Manager
 *
 * Loads and manages the third-party plugins integration implementations.
 *
 * @since TBD
 */
class Tribe__Tickets__Integrations__Manager {

	/**
	 * The current instance of the object.
	 *
	 * @since TBD
	 *
	 * @var Tribe__Tickets__Integrations__Manager
	 */
	protected static $instance;

	/**
	 * The class singleton constructor.
	 *
	 * @since TBD
	 *
	 * @return Tribe__Tickets__Integrations__Manager
	 */
	public static function instance() {
		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Conditionally loads the classes needed to integrate with third-party plugins.
	 *
	 * Third-party plugin integration classes and methods will be loaded only if
	 * supported plugins are activated.
	 *
	 * @since TBD
	 */
	public function load_integrations() {
		$this->load_freemius();
	}

	/**
	 * Loads our Freemius integration
	 *
	 * @since TBD
	 *
	 * @return bool Whether the integration was loaded.
	 */
	private function load_freemius() {
		/**
		 * Allows third-party disabling of Event Tickets and Freemius integration.
		 *
		 * @since  TBD
		 *
		 * @param bool $should_load Whether the Freemius integration should load.
		 */
		$should_load = apply_filters( 'tribe_tickets_integrations_should_load_freemius', true );

		if ( ! $should_load ) {
			return false;
		}

		tribe_singleton( 'tickets.integrations.freemius', new Tribe__Tickets__Integrations__Freemius );

		return true;
	}
}
