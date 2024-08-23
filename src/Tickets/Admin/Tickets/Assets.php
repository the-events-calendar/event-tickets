<?php
/**
 * Handles registering and setup for assets on All Tickets page.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Admin
 */

namespace TEC\Tickets\Admin\Tickets;

use TEC\Common\Contracts\Service_Provider;
use TEC\Common\StellarWP\Assets\Asset;
use Tribe__Tickets__Main;

/**
 * Class Assets.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Admin
 */
class Assets extends Service_Provider {
	/**
	 * Key for this group of assets.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $group_key = 'event-tickets-admin-attendees';

	/**
	 * Binds and sets up implementations.
	 *
	 * @since TBD
	 */
	public function register() {
		Asset::add( 'tec-tickets-all-tickets-styles', 'all-tickets.css', null, Tribe__Tickets__Main::instance()->plugin_path )
			->enqueue_on( 'admin_enqueue_scripts' )
			->set_condition( [ $this, 'should_enqueue_assets' ] )
			->register();
	}

	/**
	 * Determines if the assets should be enqueued.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function should_enqueue_assets(): bool {
		/**  @var Page $page */
		$page = tribe( Page::class );

		return $page->is_on_page();
	}
}
