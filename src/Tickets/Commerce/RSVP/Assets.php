<?php
/**
 * Handles registering and setup for assets on Ticket Commerce.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce
 */

namespace TEC\Tickets\Commerce\RSVP;

use \TEC\Common\Contracts\Service_Provider;
use TEC\Tickets\Commerce\REST\Ticket_Endpoint;
use TEC\Tickets\Commerce\Tribe__Tickets__Main;

/**
 * Class Assets.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce
 */
class Assets extends Service_Provider {

	/**
	 * Binds and sets up implementations.
	 *
	 * @since TBD
	 */
	public function register() {
		/** @var Tribe__Tickets__Main $tickets_main */
		$tickets_main = tribe( 'tickets.main' );

		tribe_asset(
			$tickets_main,
			'tribe-tickets-admin-tickets',
			'commerce/tickets.js',
			[
				'jquery',
			],
			'admin_enqueue_scripts',
			[
				'localize' => [
					'name' => 'tecTicketsCommerceTickets',
					'data' => static function () {
						return [
							'ticketEndpoint' => tribe( Ticket_Endpoint::class )->get_route_url(),
							'nonce'         => wp_create_nonce( 'wp_rest' ),
						];
					}
				]
			]
		);
	}
}
