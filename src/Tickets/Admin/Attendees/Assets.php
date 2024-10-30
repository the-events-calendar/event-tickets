<?php
/**
 * Handles registering and setup for assets on Tickets Attendees.
 *
 * @since 5.9.1
 *
 * @package TEC\Tickets\Admin
 */

namespace TEC\Tickets\Admin\Attendees;

/**
 * Class Assets.
 *
 * @since 5.9.1
 *
 * @package TEC\Tickets\Admin
 */
class Assets extends \tad_DI52_ServiceProvider {
	/**
	 * Key for this group of assets.
	 *
	 * @since 5.9.1
	 *
	 * @var string
	 */
	public static $group_key = 'event-tickets-admin-attendees';

	/**
	 * Binds and sets up implementations.
	 *
	 * @since 5.9.1
	 */
	public function register() {
		/** @var Tribe__Tickets__Main $tickets_main */
		$plugin = tribe( 'tickets.main' );

		tribe_asset(
			$plugin,
			static::$group_key . '-modal-scripts',
			'admin/tickets-attendees.js',
			[
				'jquery',
				'tribe-common',
				'tribe-tickets-loader',
			],
			null,
			[
				'groups' => [
					static::$group_key,
					'tribe-tickets-admin',
				],
			]
		);
	}
}
