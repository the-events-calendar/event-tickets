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
			[ 'jquery', 'tec-api' ],
			'admin_enqueue_scripts',
			[
				'conditionals' => [ $this, 'should_enqueue_classic_editor_assets' ],
				'localize'     => [
					'name' => 'tecTicketsCommerceTickets',
					'data' => static function () {
						return [
							'tecApiEndpoint' => '/tec/v1/tickets',
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
			'tec-tickets-commerce-rsvp-manager',
			'commerce/rsvp-manager.js',
			[
				'jquery',
				'tribe-common',
				'tribe-tickets-loader',
				'tec-tickets-commerce-rsvp',
				'tec-tickets-commerce-rsvp-tooltip',
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

		tec_asset(
			$plugin,
			Block_Editor::EDITOR_MIRROR_STYLE,
			'rsvp/editor-mirror.css',
			[ 'tec-tickets-commerce-rsvp-style' ],
			'enqueue_block_editor_assets',
			[
				'conditionals' => [ $this, 'should_enqueue_block_editor_styles' ],
			]
		);

		$stylesheet = Tribe__Templates::locate_stylesheet( 'tribe-events/tickets/rsvp.css' );

		if ( $stylesheet ) {
			tec_asset( $plugin, 'tec-tickets-commerce-rsvp-style-override', $stylesheet, [], null );
		}
	}

	/**
	 * Whether to enqueue Classic Editor RSVP metabox assets.
	 *
	 * Limits `commerce/tickets.js` to ticket-enabled post edit screens only.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function should_enqueue_classic_editor_assets(): bool {
		global $post;

		if ( empty( $post ) ) {
			return false;
		}

		return tribe_tickets_post_type_enabled( $post->post_type );
	}

	/**
	 * Whether to enqueue block editor RSVP canvas styles.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function should_enqueue_block_editor_styles(): bool {
		if ( ! is_admin() ) {
			return false;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( $screen instanceof \WP_Screen && $screen->base === 'post' ) {
			return tribe_tickets_post_type_enabled( $screen->post_type );
		}

		$post = get_post();

		if ( ! $post instanceof \WP_Post ) {
			return false;
		}

		$ticketable_post_types = (array) tribe_get_option( 'ticket-enabled-post-types', [] );

		return in_array( $post->post_type, $ticketable_post_types, true );
	}
}
