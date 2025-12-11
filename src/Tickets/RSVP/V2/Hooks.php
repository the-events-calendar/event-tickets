<?php
/**
 * Handles hooking all the actions and filters used by RSVP V2.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */

namespace TEC\Tickets\RSVP\V2;

use TEC\Common\Contracts\Service_Provider;

/**
 * Class Hooks.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */
class Hooks extends Service_Provider {

	/**
	 * Binds and sets up implementations.
	 *
	 * @since TBD
	 */
	public function register() {
		$this->add_actions();
		$this->add_filters();
	}

	/**
	 * Adds the actions required by RSVP V2.
	 *
	 * @since TBD
	 */
	protected function add_actions() {
		add_action( 'rest_api_init', [ $this, 'register_rest_endpoints' ] );
	}

	/**
	 * Adds the filters required by RSVP V2.
	 *
	 * @since TBD
	 */
	protected function add_filters() {
		// Placeholder for future filters.
	}

	/**
	 * Register the REST API endpoints.
	 *
	 * @since TBD
	 */
	public function register_rest_endpoints() {
		$this->container->make( REST\Order_Endpoint::class )->register();
		$this->container->make( REST\Ticket_Endpoint::class )->register();
	}
}
