<?php
/**
 * Handles registering and setup for assets on Ticket Commerce.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce
 */

namespace TEC\Tickets\Commerce\RSVP;

use \TEC\Common\Contracts\Service_Provider;
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

		tribe_asset( $tickets_main, 'tribe-tickets-admin-tickets', 'commerce/tickets.js', [
				'jquery',
			], 'admin_enqueue_scripts', [
				'localize' => [
					'name' => 'tecTicketsCommerceTickets',
					'data' => static function () {
						return [
							'ticketEndpoint' => tribe( Ticket_Endpoint::class )->get_route_url(),
							'nonce'          => wp_create_nonce( 'wp_rest' ),
						];
					}
				]
			] );


		$this->assets();
	}

	/**
	 * Register Front End Assets.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function assets() {
		$plugin = Tribe__Tickets__Main::instance();

		tec_asset( $plugin, 'tec-tickets-commerce-rsvp', 'commerce/rsvp-block.js', [ 'jquery' ], null, [
			'groups'       => 'tec-tickets-commerce-rsvp',
				'localize' => [
					'name' => 'TribeRsvp',
					'data' => fn() => [
						'ajaxurl' => admin_url( 'admin-ajax.php', ( is_ssl() ? 'https' : 'http' ) ),
						'nonces'  => [
							'rsvpHandle' => wp_create_nonce( 'tribe_tickets_rsvp_handle' )
						],
					],
				],
			] );

		tec_asset( $plugin, 'tribe-tickets-gutenberg-block-rsvp-style', 'rsvp/frontend.css' );

		tec_asset( $plugin, 'tribe-tickets-rsvp-ari', 'commerce/rsvp-ari.js', [ 'jquery', 'wp-util', 'tribe-common' ], null, [
				'groups'       => 'tec-tickets-commerce-rsvp',
				'conditionals' => [ $this, 'should_enqueue_ari' ],
				'localize'     => [
					'name' => 'TribeRsvp',
					'data' => fn() => [
						'ajaxurl' => admin_url( 'admin-ajax.php', ( is_ssl() ? 'https' : 'http' ) ),
						'nonces'  => [
							'rsvpHandle' => wp_create_nonce( 'tribe_tickets_rsvp_handle' )
						],
					],
				],
			] );

		tec_asset( $plugin, 'tribe-tickets-rsvp-manager', 'commerce/rsvp-manager.js', [
				'jquery',
				'tribe-common',
				'tribe-tickets-rsvp-block',
				'tribe-tickets-rsvp-tooltip',
				'tribe-tickets-rsvp-ari',
			], null, [
				'localize' => [
					'name' => 'TribeRsvp',
					'data' => static function () {
						return [
							'ajaxurl'    => admin_url( 'admin-ajax.php', ( is_ssl() ? 'https' : 'http' ) ),
							'cancelText' => __( 'Are you sure you want to cancel?', 'event-tickets' ),
						];
					},
				],
				'groups'       => 'tec-tickets-commerce-rsvp',
			] );


		tec_asset( $plugin, 'tribe-tickets-rsvp-tooltip', 'commerce/rsvp-tooltip.js', [
				'jquery',
				'tribe-common',
				'tribe-tooltipster',
			], null, [
			'groups'       => 'tec-tickets-commerce-rsvp',
			] );

		tec_asset( $plugin, 'tribe-tickets-rsvp-style', 'rsvp.css', [ 'tribe-common-skeleton-style', 'tribe-common-responsive' ], null );

		$stylesheet = Tribe__Templates::locate_stylesheet( 'tribe-events/tickets/rsvp.css' );
		if ( $stylesheet ) {
			tec_asset( $plugin, 'tribe-tickets-rsvp-style-override', $stylesheet, [], null );
		}
	}

	/**
	 * Determine whether we should enqueue the ARI assets.
	 *
	 * @since 5.0.0
	 *
	 * @return bool Whether we should enqueue the ARI assets.
	 */
	public function should_enqueue_ari() {
		return class_exists( 'Tribe__Tickets_Plus__Main' );
	}
}
