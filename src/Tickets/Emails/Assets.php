<?php
/**
 * Handles registering and setup for assets on Tickets Emails.
 *
 * @since 5.5.6
 *
 * @package TEC\Tickets\Emails
 */

namespace TEC\Tickets\Emails;

use \TEC\Common\Contracts\Service_Provider;

/**
 * Class Assets.
 *
 * @since 5.5.6
 *
 * @package TEC\Tickets\Emails
 */
class Assets extends Service_Provider {

	/**
	 * Key for this group of assets.
	 *
	 * @since 5.5.7
	 *
	 * @var string
	 */
	public static $group_key = 'tec-tickets-admin-emails';

	/**
	 * Binds and sets up implementations.
	 *
	 * @since 5.5.6
	 */
	public function register() {
		/** @var Tribe__Tickets__Main $tickets_main */
		$plugin = tribe( 'tickets.main' );

		tec_asset(
			$plugin,
			static::$group_key . '-modal-scripts',
			'admin/tickets-emails.js',
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
