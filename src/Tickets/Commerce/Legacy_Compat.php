<?php
/**
 * Handles registering and setup for legacy compatibility from Ticket Commerce towards the old Tribe Commerce.
 *
 * @since 5.1.6
 *
 * @package TEC\Tickets\Commerce
 */

namespace TEC\Tickets\Commerce;

use \tad_DI52_ServiceProvider;

/**
 * Class Legacy Compat.
 *
 * @since 5.1.6
 *
 * @package TEC\Tickets\Commerce
 */
class Legacy_Compat extends tad_DI52_ServiceProvider {

	/**
	 * Binds and sets up implementations.
	 *
	 * @since 5.1.6
	 */
	public function register() {
//		$this->add_actions();
		$this->add_filters();
	}

	/**
	 * Adds the actions required to handle legacy compatibility.
	 *
	 * @since TBD
	 */
	protected function add_actions() {

	}

	/**
	 * Adds the filters required to handle legacy compatibility.
	 *
	 * @since TBD
	 */
	protected function add_filters() {
		add_filter( 'tribe_events_tickets_module_name', [ $this, 'set_legacy_module_name' ] );

		// Disable TribeCommerce for new installations.
		add_filter( 'tribe_tickets_commerce_paypal_is_active', 'tec_tribe_commerce_is_available' );
	}

	/**
	 * Show the legacy PayPal as not recommended.
	 *
	 * @since TBD
	 *
	 * @param $name string Name of the provider.
	 *
	 * @return string
	 */
	public function set_legacy_module_name( $name ) {
		return $name != 'Tribe Commerce' ? $name : __( 'Tribe Commerce ( Legacy PayPal, not recommended )', 'event-tickets' );
	}

}