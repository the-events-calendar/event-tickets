<?php
/**
 * Handles hooking all the actions and filters used by the admin area.
 *
 * To remove a filter:
 * remove_filter( 'some_filter', [ tribe( TEC\Tickets\Admin\Tickets\Hooks::class ), 'some_filtering_method' ] );
 *
 * To remove an action:
 * remove_action( 'some_action', [ tribe( TEC\Tickets\Admin\Tickets\Hooks::class ), 'some_method' ] );
 *
 * @since   5.14.0
 *
 * @package TEC\Tickets\Admin
 */

namespace TEC\Tickets\Admin\Tickets;

use TEC\Common\Contracts\Service_Provider;

/**
 * Class Hooks.
 *
 * @since   5.14.0
 *
 * @package TEC\Tickets\Admin
 */
class Hooks extends Service_Provider {
	/**
	 * Binds and sets up implementations.
	 *
	 * @since 5.14.0
	 */
	public function register() {
		$this->add_actions();
		$this->add_filters();
	}

	/**
	 * Adds the actions for the Admin All Tickets page.
	 *
	 * @since 5.14.0
	 */
	protected function add_actions() {
		add_action( 'admin_menu', tribe_callback( Page::class, 'add_tec_tickets_admin_tickets_page' ), 15 );
		add_action( 'current_screen', tribe_callback( Screen_Options::class, 'init' ) );
	}

	/**
	 * Adds the filters for the Admin All Tickets page.
	 *
	 * @since 5.14.0
	 */
	protected function add_filters() {
		add_filter( 'set-screen-option', [ tribe( Screen_Options::class ), 'filter_set_screen_options' ], 10, 3 );
	}
}
