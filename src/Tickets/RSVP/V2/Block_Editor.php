<?php
/**
 * Block Editor delegate for RSVP V2.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */

namespace TEC\Tickets\RSVP\V2;

use TEC\Tickets\REST\TEC\V1\Endpoints\Ticket as Ticket_Endpoint;
use WP_Post;

/**
 * Class Block_Editor
 *
 * Handles Block Editor integration for RSVP V2.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */
class Block_Editor {
	/**
	 * Style handle for RSVP V2 editor canvas overrides (mirror affordances).
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const EDITOR_MIRROR_STYLE = 'tec-tickets-rsvp-v2-block-editor-mirror-style';

	/**
	 * Register `register_block_type_args` filter so the canvas iframe (WP 6.3+)
	 * also receives the shared frontend RSVP styles via the block's `editorStyle`.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'register_block_type_args', [ $this, 'add_rsvp_block_editor_style_args' ], 10, 2 );
	}

	/**
	 * Add V2 RSVP configuration to the block editor config.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $config The editor configuration.
	 *
	 * @return array<string,mixed> The modified editor configuration.
	 */
	public function add_rsvp_v2_editor_config( array $config ): array {
		$config['tickets'] ??= [];

		$config['tickets']['rsvpV2'] = [
			'enabled'         => true,
			'ticketsEndpoint' => '/tec/v1/tickets',
			'ticketType'      => Constants::TC_RSVP_TYPE,
			'initialTicket'   => $this->get_initial_rsvp_ticket(),
		];

		return $config;
	}

	/**
	 * Returns the RSVP ticket for the current post in REST-shaped format.
	 *
	 * Preloaded into the block editor so the RSVP block can render without
	 * waiting for an async fetch on first paint.
	 *
	 * @since TBD
	 *
	 * @return array<string,mixed>|null Formatted ticket entity or null when none exists.
	 */
	private function get_initial_rsvp_ticket(): ?array {
		$post_id = get_the_ID();

		if ( ! $post_id ) {
			return null;
		}

		$ticket = tribe( Ticket::class )->get_for_event( (int) $post_id );

		if ( ! $ticket ) {
			return null;
		}

		if ( ! function_exists( 'tec_tc_get_ticket' ) ) {
			return null;
		}

		$ticket_post = tec_tc_get_ticket( (int) $ticket->ID );

		if ( ! $ticket_post instanceof WP_Post ) {
			return null;
		}

		/** @var Ticket_Endpoint $endpoint */
		$endpoint = tribe( Ticket_Endpoint::class );

		$initial_ticket = $endpoint->get_formatted_entity( $ticket_post );

		/**
		 * Filters the initial RSVP ticket data preloaded into the block editor.
		 *
		 * Allows ET+ and other add-ons to inject additional fields (e.g.,
		 * has_attendee_info_fields, field_labels) into the server-preloaded
		 * ticket data so the RSVP block renders correctly on first paint.
		 *
		 * @since TBD
		 *
		 * @param array<string,mixed> $initial_ticket The formatted ticket entity.
		 * @param int                 $post_id        The current post ID.
		 * @param int                 $ticket_id      The ticket post ID.
		 */
		return apply_filters( 'tec_tickets_rsvp_v2_initial_ticket', $initial_ticket, $post_id, (int) $ticket_post->ID );
	}

	/**
	 * Use the pre-render block filter as an action to ensure that Tickets' block assets
	 * are enqueued for server-side rendering contexts (REST API, etc.).
	 *
	 * Note: this does NOT fire in the block editor admin (blocks render client-side there).
	 * Editor assets are handled by `enqueue_rsvp_block_editor_styles` instead.
	 *
	 * @since TBD
	 *
	 * @param string|null         $pre_render   The pre-rendered content. Default null.
	 * @param array<string,mixed> $parsed_block The parsed block data.
	 *
	 * @return string|null Always the input value.
	 */
	public function enqueue_tickets_block_assets( $pre_render, array $parsed_block ) {
		if ( isset( $parsed_block['blockName'] ) && $parsed_block['blockName'] === 'tribe/tickets' ) {
			tribe_asset_enqueue_group( 'tribe-tickets-block-assets' );
		}

		return $pre_render;
	}

	/**
	 * Attach frontend RSVP styles to the tribe/rsvp block type so WordPress
	 * automatically loads them inside the editor canvas iframe (WP 6.3+).
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $args       Block registration arguments.
	 * @param string              $block_type The block name.
	 *
	 * @return array<string,mixed>
	 */
	public function add_rsvp_block_editor_style_args( array $args, string $block_type ): array {
		if ( 'tribe/rsvp' !== $block_type ) {
			return $args;
		}

		$canvas_styles = [
			'tribe-common-skeleton-style',
			'tribe-common-responsive',
			'tribe-common-full-style',
			'tec-tickets-commerce-rsvp-style',
			self::EDITOR_MIRROR_STYLE,
		];

		$existing = isset( $args['editorStyle'] ) ? (array) $args['editorStyle'] : [];

		$args['editorStyle'] = array_values( array_unique( array_merge( $existing, $canvas_styles ) ) );

		return $args;
	}

	/**
	 * Whether RSVP editor canvas styles should be enqueued.
	 *
	 * Called during `enqueue_block_editor_assets` and `enqueue_block_assets`.
	 * Uses `get_current_screen()` so it works even when `get_post()` is not yet set.
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

		// Fallback: if screen is not set yet (e.g. during REST block rendering), check the post.
		$post = get_post();

		if ( ! $post instanceof \WP_Post ) {
			return false;
		}

		$ticketable_post_types = (array) tribe_get_option( 'ticket-enabled-post-types', [] );

		return in_array( $post->post_type, $ticketable_post_types, true );
	}

	/**
	 * Enqueue RSVP frontend styles in the block editor.
	 *
	 * Hooked to both `enqueue_block_editor_assets` (editor chrome + non-iframe WP < 6.3)
	 * and `enqueue_block_assets` (editor canvas iframe WP 6.3+).
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function enqueue_rsvp_block_editor_styles(): void {
		if ( ! $this->should_enqueue_block_editor_styles() ) {
			return;
		}

		tribe_asset_enqueue( 'tribe-common-skeleton-style' );
		tribe_asset_enqueue( 'tribe-common-responsive' );
		tribe_asset_enqueue( 'tribe-common-full-style' );
		tribe_asset_enqueue( 'tribe-tickets-gutenberg-block-rsvp-style' );
		tribe_asset_enqueue( 'tec-tickets-commerce-rsvp-style' );
		tribe_asset_enqueue( self::EDITOR_MIRROR_STYLE );
	}
}
