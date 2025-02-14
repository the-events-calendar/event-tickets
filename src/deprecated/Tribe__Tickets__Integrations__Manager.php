<?php
_deprecated_file( __FILE__, '5.6.0', 'No direct replacement.' );

/**
 * Class Tribe__Tickets__Integrations__Manager
 *
 * Loads and manages the third-party plugins integration implementations.
 *
 * @depreacated 5.6.0
 *
 * @since 4.11.5
 */
class Tribe__Tickets__Integrations__Manager {

	/**
	 * The current instance of the object.
	 *
	 * @depreacated 5.6.0
	 * @since 4.11.5
	 *
	 * @var Tribe__Tickets__Integrations__Manager
	 */
	protected static $instance;

	/**
	 * The class singleton constructor.
	 *
	 * @depreacated 5.6.0
	 * @since 4.11.5
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
	 * @depreacated 5.6.0
	 * @since 4.11.5
	 */
	public function load_integrations() {
		tribe_singleton( 'tickets.integrations.freemius', Tribe__Tickets__Integrations__Freemius::class, [ 'setup' ] );
		$this->hook();
	}

	/**
	 * Loads our Freemius integration.
	 *
	 * @depreacated 5.6.0
	 * @since 4.11.5
	 */
	public function load_freemius() {
		tribe( 'tickets.integrations.freemius' );
	}

	/**
	 * Hooks for the integrations manager.
	 *
	 * @depreacated 5.6.0
	 * @since 5.4.1
	 */
	public function hook() {
		add_action( 'init', [ $this, 'load_freemius' ], 15 );
	}
}
