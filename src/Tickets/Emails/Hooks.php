<?php
/**
 * Handles hooking all the actions and filters used by the module.
 *
 * To remove a filter:
 * remove_filter( 'some_filter', [ tribe( TEC\Tickets\Emails\Hooks::class ), 'some_filtering_method' ] );
 * remove_filter( 'some_filter', [ tribe( 'tickets.emails.hooks' ), 'some_filtering_method' ] );
 *
 * To remove an action:
 * remove_action( 'some_action', [ tribe( TEC\Tickets\Emails\Hooks::class ), 'some_method' ] );
 * remove_action( 'some_action', [ tribe( 'tickets.emails.hooks' ), 'some_method' ] );
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Emails
 */

namespace TEC\Tickets\Emails;

use \tad_DI52_ServiceProvider;

/**
 * Class Hooks.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Emails
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
	 * Adds the actions required by each Tickets Emails component.
	 *
	 * @since TBD
	 */
	protected function add_actions() {
		add_action( 'tribe_settings_do_tabs', [ tribe( Emails_Tab::class ), 'register_tab' ], 17 );
	}

	/**
	 * Adds the filters required by each Tickets Emails component.
	 *
	 * @since TBD
	 */
	protected function add_filters() {
		add_filter( 'tec_tickets_settings_tabs_ids', [ tribe( Emails_Tab::class ), 'settings_add_tab_id' ] );
	}
}
