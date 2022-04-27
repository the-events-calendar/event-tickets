<?php
/**
 * Handles hooking all the actions and filters used by the admin area.
 *
 * To remove a filter:
 * remove_filter( 'some_filter', [ tribe( TEC\Tickets\Admin\Hooks::class ), 'some_filtering_method' ] );
 * remove_filter( 'some_filter', [ tribe( 'tickets.admin.hooks' ), 'some_filtering_method' ] );
 *
 * To remove an action:
 * remove_action( 'some_action', [ tribe( TEC\Tickets\Admin\Hooks::class ), 'some_method' ] );
 * remove_action( 'some_action', [ tribe( 'tickets.admin.hooks' ), 'some_method' ] );
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Admin
 */

namespace TEC\Tickets\Admin;


/**
 * Class Hooks.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Admin
 */
class Hooks extends \tad_DI52_ServiceProvider {

	/**
	 * Binds and sets up implementations.
	 *
	 * @since TBD
	 */
	public function register() {
		tribe( Upsell::class )->hooks();
	}

}
