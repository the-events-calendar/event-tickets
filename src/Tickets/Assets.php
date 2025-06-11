<?php
/**
 * Handles registering and setup for assets on Tickets.
 *
 * @since 5.1.6
 *
 * @package TEC\Tickets
 */

namespace TEC\Tickets;

use \TEC\Common\Contracts\Service_Provider;

/**
 * Class Assets.
 *
 * @since 5.1.6
 *
 * @package TEC\Tickets
 */
class Assets extends Service_Provider {

	/**
	 * Binds and sets up implementations.
	 *
	 * @since 5.1.6
	 */
	public function register() {
		$plugin = tribe( 'tickets.main' );

		tec_asset(
			$plugin,
			'tribe-tickets-provider',
			'tickets-provider.js',
			[
				'tribe-common',
			],
			null,
			[
				'localize' => [
					'name' => 'tecTicketsSettings',
					'data' => [
						'debug' => defined( 'WP_DEBUG' ) && WP_DEBUG
					],
				],
			]
		);

	}
}
