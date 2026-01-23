<?php
/**
 * Handles registering and setup for assets on RSVP V2.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */

namespace TEC\Tickets\RSVP\V2;

use TEC\Tickets\RSVP\V2\Constants;
use TEC\Tickets\RSVP\V2\REST\Order_Endpoint;
use Tribe__Templates;
use Tribe__Tickets__Main;

/**
 * Class Assets.
 *
 * Registers RSVP V2 assets including CSS and JavaScript.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */
class Assets {
	/**
	 * Binds and sets up implementations.
	 *
	 * @since TBD
	 */
	public function register() {
		/** @var Tribe__Tickets__Main $plugin */
		$plugin = tribe( 'tickets.main' );

		tec_asset(
			$plugin,
			'tribe-tickets-admin-tickets',
			'commerce/tickets.js',
			[ 'jquery' ],
			'admin_enqueue_scripts',
			[
				'localize' => [
					'name' => 'tecTicketsCommerceTickets',
					'data' => static function () {
						return [
							'tecApiEndpoint' => rest_url( 'tec/v1/tickets' ),
							'ticketType'     => Constants::TC_RSVP_TYPE,
						];
					},
				],
			]
		);

		tec_asset(
			$plugin,
			'tec-tickets-commerce-rsvp',
			'commerce/rsvp-block.js',
			[ 'jquery' ],
			null,
			[
				'groups'   => 'tec-tickets-commerce-rsvp',
				'localize' => [
					'name' => 'TecRsvp',
					'data' => fn() => [
						'nonces'        => [
							'rsvpHandle' => wp_create_nonce( 'tribe_tickets_rsvp_handle' ),
						],
						'orderEndpoint' => tribe_callback( Order_Endpoint::class, 'get_route_url' )(),
						'nonce'         => wp_create_nonce( 'wp_rest' ),
						'cancelText'    => __( 'Are you sure you want to cancel?', 'event-tickets' ),
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
			]
		);

		tec_asset(
			$plugin,
			'tec-tickets-commerce-rsvp-manager',
			'commerce/rsvp-manager.js',
			[
				'jquery',
				'tribe-common',
				'tribe-tickets-loader',
				'tec-tickets-commerce-rsvp',
				'tec-tickets-commerce-rsvp-tooltip',
				'tec-tickets-commerce-rsvp-ari',
			],
			null,
			[
				'groups' => 'tec-tickets-commerce-rsvp',
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
				'groups' => 'tec-tickets-commerce-rsvp',
			]
		);

		tec_asset(
			$plugin,
			'tec-tickets-commerce-rsvp-style',
			'rsvp.css',
			[ 'tribe-common-skeleton-style', 'tribe-common-responsive' ]
		);

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
	public function should_enqueue_ari(): bool {
		return class_exists( 'Tribe__Tickets_Plus__Main' );
	}
}
