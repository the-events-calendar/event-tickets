<?php
/**
 * Handles registering and setup for assets on Tickets Attendees.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Emails
 */

namespace TEC\Tickets\Admin\Attendees;

/**
 * Class Assets.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Emails
 */
class Assets extends \tad_DI52_ServiceProvider {
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
