<?php
/**
 * Handles registering and setup for assets on All Tickets page.
 *
 * @since 5.14.0
 *
 * @package TEC\Tickets\Admin
 */

namespace TEC\Tickets\Admin\Tickets;

use TEC\Common\Contracts\Service_Provider;
use TEC\Common\Asset;
use Tribe__Tickets__Main;

/**
 * Class Assets.
 *
 * @since 5.14.0
 *
 * @package TEC\Tickets\Admin
 */
class Assets extends Service_Provider {
	/**
	 * Key for this group of assets.
	 *
	 * @since 5.14.0
	 *
	 * @var string
	 */
	public static $group_key = 'event-tickets-admin-attendees';

	/**
	 * Binds and sets up implementations.
	 *
	 * @since 5.14.0
	 */
	public function register() {
		Asset::add( 'tec-tickets-admin-tickets-table-styles', 'tickets-admin-tickets.css' )
			->enqueue_on( 'admin_enqueue_scripts' )
			->add_to_group_path( Tribe__Tickets__Main::class )
			->set_condition( [ $this, 'should_enqueue_assets' ] )
			->register();
	}

	/**
	 * Determines if the assets should be enqueued.
	 *
	 * @since 5.14.0
	 *
	 * @return bool
	 */
	public function should_enqueue_assets(): bool {
		return tribe( Page::class )->is_on_page();
	}
}
