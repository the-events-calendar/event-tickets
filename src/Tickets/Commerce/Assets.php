<?php
/**
 * Handles registering and setup for assets on Ticket Commerce.
 *
 * @since 5.1.6
 *
 * @package TEC\Tickets\Commerce
 */

namespace TEC\Tickets\Commerce;

use \tad_DI52_ServiceProvider;

/**
 * Class Assets.
 *
 * @since 5.1.6
 *
 * @package TEC\Tickets\Commerce
 */
class Assets extends tad_DI52_ServiceProvider {

	/**
	 * Binds and sets up implementations.
	 *
	 * @since 5.1.6
	 */
	public function register() {
		tribe_asset(
			\Tribe__Tickets__Main::instance(),
			'tribe-tickets-admin-commerce-settings',
			'admin/tickets-commerce-settings.js',
			[ 'jquery' ],
			'admin_enqueue_scripts'
		);
	}

}