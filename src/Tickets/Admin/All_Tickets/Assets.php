<?php
/**
 * Handles registering and setup for assets on All Tickets page.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Admin
 */

namespace TEC\Tickets\Admin\All_Tickets;

use TEC\Common\Contracts\Service_Provider;

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
		/** @var Tribe__Tickets__Main $tickets_main */
		$plugin = tribe( 'tickets.main' );

		tribe_asset(
			$plugin,
			'tec-tickets-all-tickets-styles',
			'all-tickets.css',
			[],
			'admin_enqueue_scripts',
			[
				'conditionals' => [ $this, 'should_enqueue_assets' ],
			]
		);
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
