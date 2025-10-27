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
 * @since 5.3.4
 *
 * @package TEC\Tickets\Admin
 */

namespace TEC\Tickets\Admin;

/**
 * Class Hooks.
 *
 * @since 5.3.4
 *
 * @package TEC\Tickets\Admin
 */
class Hooks extends \TEC\Common\Contracts\Service_Provider {

	/**
	 * Binds and sets up implementations.
	 *
	 * @since 5.3.4
	 */
	public function register() {
		tribe( Upsell::class )->hooks();
		tribe( Plugin_Action_Links::class )->hooks();
		tribe( Glance_Items::class )->hooks();
		add_filter( 'tec_get_admin_region', [ $this, 'is_et_admin_page' ] );
	}

	/**
	 * Checks if the current admin page is an ET admin page.
	 *
	 * @since 5.26.7
	 *
	 * @param string $region The current admin region.
	 *
	 * @return bool Whether the current admin page is an ET admin page.
	 */
	public function is_et_admin_page( $region ) {
		if ( ! is_admin() ) {
			return false;
		}

		$parent = get_admin_page_parent();
		if ( ! $parent ) {
			return false;
		}

		if ( str_contains( $parent, 'tec-tickets' ) ) {
			return 'tickets';
		}

		return $region;
	}
}
