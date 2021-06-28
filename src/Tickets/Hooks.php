<?php
/**
 * Handles hooking all the actions and filters used by the module.
 *
 * To remove a filter:
 * remove_filter( 'some_filter', [ tribe( TEC\Tickets\Hooks::class ), 'some_filtering_method' ] );
 * remove_filter( 'some_filter', [ tribe( 'tickets.hooks' ), 'some_filtering_method' ] );
 *
 * To remove an action:
 * remove_action( 'some_action', [ tribe( TEC\Tickets\Hooks::class ), 'some_method' ] );
 * remove_action( 'some_action', [ tribe( 'tickets..hooks' ), 'some_method' ] );
 *
 * @since TBD
 *
 * @package TEC\Tickets
 */

namespace TEC\Tickets;

use \tad_DI52_ServiceProvider;

/**
 * Class Hooks.
 *
 * @since TBD
 *
 * @package TEC\Tickets
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
	 * Adds the actions required by each Tickets component.
	 *
	 * @since TBD
	 */
	protected function add_actions() {

	}

	/**
	 * Adds the filters required by each Tickets component.
	 *
	 * @since TBD
	 */
	protected function add_filters() {

	}

}