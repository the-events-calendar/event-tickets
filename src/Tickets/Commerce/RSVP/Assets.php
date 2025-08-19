<?php
/**
 * Handles registering and setup for assets on Ticket Commerce.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce
 */

namespace TEC\Tickets\Commerce\RSVP;

use TEC\Tickets\Commerce\RSVP\REST\Order_Endpoint;
use TEC\Common\Contracts\Service_Provider;
use TEC\Tickets\Commerce\REST\Ticket_Endpoint;
use Tribe__Tickets__Main;
use Tribe__Templates;

/**
 * Class Assets.
 *
 * @since   TBD
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

		tec_asset(
			$tickets_main,
			'tribe-tickets-admin-tickets',
			'commerce/tickets.js',
			[ 'jquery' ],
			'admin_enqueue_scripts',
			[
				'localize' => [
					'name' => 'tecTicketsCommerceTickets',
					'data' => static function () {
						return [
							'ticketEndpoint' => tribe( Ticket_Endpoint::class )->get_route_url(),
							'nonce'          => wp_create_nonce( 'wp_rest' ),
						];
					},
				],
			]
		);


		$this->assets();
	}

	/**
	 * Register Front End Assets.
	 *
	 * @since TBD
	 */
	public function assets() {
		$plugin = Tribe__Tickets__Main::instance();

		tec_asset(
			$plugin,
			'tec-tickets-commerce-rsvp',
			'commerce/rsvp-block.js',
			[ 'jquery' ],
			null,
			[
				'groups'       => 'tec-tickets-commerce-rsvp',
				'localize' => [
					'name' => 'TecRsvp',
					'data' => fn() => [
						'nonces'  => [
							'rsvpHandle' => wp_create_nonce( 'tribe_tickets_rsvp_handle' )
						],
						'orderEndpoint' => tribe( Order_Endpoint::class )->get_route_url(),
						'nonce'         => wp_create_nonce( 'wp_rest' ),
						'cancelText' => __( 'Are you sure you want to cancel?', 'event-tickets' ),
					],
				],
			]
		);

		tec_asset( $plugin, 'tribe-tickets-gutenberg-block-rsvp-style', 'rsvp/frontend.css' );

		tec_asset(
			$plugin,
			'tec-tickets-commerce-rsvp-ari',
			'commerce/rsvp-ari.js',
			[ 'jquery', 'wp-util', 'tribe-common' ],
			null,
			[
				'groups'       => 'tec-tickets-commerce-rsvp',
				'conditionals' => [ $this, 'should_enqueue_ari' ],
/*				'localize'     => [
					'name' => 'TecRsvp',
					'data' => fn() => [
						'nonces'  => [
							'rsvpHandle' => wp_create_nonce( 'tribe_tickets_rsvp_handle' )
						],
					],
				],*/
			]
		);

		tec_asset(
			$plugin,
			'tec-tickets-commerce-rsvp-manager',
			'commerce/rsvp-manager.js',
			[
				'jquery',
				'tribe-common',
				'tec-tickets-commerce-rsvp',
				'tec-tickets-commerce-rsvp-tooltip',
				'tec-tickets-commerce-rsvp-ari',
			],
			null,
			[
				'groups'       => 'tec-tickets-commerce-rsvp',
/*				'localize' => [
					'name' => 'TecRsvp',
					'data' => static function () {
						return [
							'cancelText' => __( 'Are you sure you want to cancel?', 'event-tickets' ),
						];
					},
				],*/
			]
		);


		tec_asset(
			$plugin,
			'tec-tickets-commerce-rsvp-tooltip',
			'commerce/rsvp-tooltip.js',
			[
				'jquery',
				'tribe-common',
				'tribe-tooltipster',
			],
			null,
			[
			'groups'       => 'tec-tickets-commerce-rsvp',
			]
		);

		tec_asset( $plugin, 'tec-tickets-commerce-rsvp-style', 'rsvp.css', [ 'tribe-common-skeleton-style', 'tribe-common-responsive' ], null );

		$stylesheet = Tribe__Templates::locate_stylesheet( 'tribe-events/tickets/rsvp.css' );
		if ( $stylesheet ) {
			tec_asset( $plugin, 'tec-tickets-commerce-rsvp-style-override', $stylesheet, [], null );
		}
	}

	/**
	 * Determine whether we should enqueue the ARI assets.
	 *
	 * @since TBD
	 *
	 * @return bool Whether we should enqueue the ARI assets.
	 */
	public function should_enqueue_ari() {
		return class_exists( 'Tribe__Tickets_Plus__Main' );
	}
}
