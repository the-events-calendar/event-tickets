<?php
/**
 * Block Editor delegate for RSVP V2.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */

namespace TEC\Tickets\RSVP\V2;

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
	 * Add V2 RSVP configuration to the block editor config.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $config The editor configuration.
	 *
	 * @return array<string,mixed> The modified editor configuration.
	 */
	public function add_rsvp_v2_editor_config( array $config ): array {
		$config['tickets'] = $config['tickets'] ?? [];

		$config['tickets']['rsvpV2'] = [
			'enabled'         => true,
			'ticketsEndpoint' => '/tec/v1/tickets',
			'ticketType'      => Constants::TC_RSVP_TYPE,
		];

		return $config;
	}

	/**
	 * Use the pre-render block filter as an action to ensure that Tickets' block assets
	 * are enqueued.
	 *
	 * The Tickets' block assets need to be enqueued before the block renders to ensure the
	 * scripts queued with it will not be dequeued by the block rendering process.
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
}
