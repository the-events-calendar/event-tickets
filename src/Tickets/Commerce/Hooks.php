<?php
/**
 * Handles hooking all the actions and filters used by the module.
 *
 * To remove a filter:
 * remove_filter( 'some_filter', [ tribe( TEC\Tickets\Commerce\Hooks::class ), 'some_filtering_method' ] );
 * remove_filter( 'some_filter', [ tribe( 'tickets.commerce.hooks' ), 'some_filtering_method' ] );
 *
 * To remove an action:
 * remove_action( 'some_action', [ tribe( TEC\Tickets\Commerce\Hooks::class ), 'some_method' ] );
 * remove_action( 'some_action', [ tribe( 'tickets.commerce.hooks' ), 'some_method' ] );
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce
 */

namespace TEC\Tickets\Commerce;

use \tad_DI52_ServiceProvider;
use Tribe\Tickets\Shortcodes\Tribe_Tickets_Checkout;

/**
 * Class Hooks.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce
 */
class Hooks extends tad_DI52_ServiceProvider {

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
	 * Adds the actions required by each Tickets Commerce component.
	 *
	 * @since TBD
	 */
	protected function add_actions() {
		add_action( 'admin_init', [ $this, 'register_assets' ] );
	}

	/**
	 * Adds the filters required by each Tickets Commerce component.
	 *
	 * @since TBD
	 */
	protected function add_filters() {
		add_filter( 'tribe_shortcodes', [ $this, 'filter_register_shortcodes' ] );
	}

	/**
	 * Register shortcodes.
	 *
	 * @see   \Tribe\Shortcode\Manager::get_registered_shortcodes()
	 *
	 * @since TBD
	 *
	 * @param array $shortcodes An associative array of shortcodes in the shape `[ <slug> => <class> ]`.
	 *
	 * @return array
	 */
	public function filter_register_shortcodes( array $shortcodes ) {
		$shortcodes['tribe_tickets_checkout'] = Tribe_Tickets_Checkout::class;

		return $shortcodes;
	}
}