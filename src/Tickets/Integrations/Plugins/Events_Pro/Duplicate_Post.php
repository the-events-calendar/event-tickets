<?php
/**
 * Used for hooking into ECP to extend the functionality of duplicating events.
 */

namespace TEC\Tickets\Integrations\Plugins\Events_Pro;

use TEC\Tickets\Integrations\Integration_Abstract;
use TEC\Common\Integrations\Traits\Plugin_Integration;
use Tribe__Tickets__Tickets;
use Tribe\Tickets\Events\Attendees_List;
use Tribe__Tickets__Global_Stock as Global_Stock;

/**
 * Class Duplicate_Post
 *
 * Extends the cloning capability introduced by Events Pro to also handle the duplication of tickets
 * to new posts.
 *
 * @since 5.10.0
 *
 * @package TEC\Events\Integrations\Plugins\Events_Pro
 */
class Duplicate_Post extends Integration_Abstract {

	use Plugin_Integration;


	/**
	 * @inheritDoc
	 */
	public static function get_slug(): string {
		return 'events-pro-duplicate-post';
	}

	/**
	 * @inheritDoc
	 *
	 * @return bool Whether or not integrations should load.
	 */
	public function load_conditionals(): bool {
		return true;
	}

	/**
	 * @inheritDoc
	 */
	protected function load(): void {
		add_action(
			'tec_events_pro_custom_tables_v1_after_duplicate_event',
			[
				$this,
				'duplicate_tickets_to_new_post',
			],
			10,
			2
		);

		add_filter( 'tec_events_pro_custom_tables_v1_duplicate_meta_data', [ $this, 'add_tickets_meta_to_duplicate' ], 10, 2 );

		add_action( 'tec_tickets_tickets_duplicated', [ $this, 'remap_fields_pointing_to_old_tickets_after_duplicate' ], 10, 3 );
	}

	/**
	 * Duplicate tickets to a new post.
	 *
	 * @since 5.10.0
	 *
	 * @param \WP_Post $new_post New post object.
	 * @param \WP_Post $post Original post object.
	 *
	 * @return void
	 */
	public function duplicate_tickets_to_new_post( \WP_Post $new_post, \WP_Post $post ) {
		$new_post_id = $new_post->ID;
		$tickets     = Tribe__Tickets__Tickets::get_all_event_tickets( $post->ID );

		// If we have no tickets, return.
		if ( empty( $tickets ) ) {
			return;
		}

		$duplicated_ticket_ids = [];

		// You technically can have multiple providers if you have RSVP + a ticket provider.
		foreach ( $tickets as $ticket ) {

			$provider = tribe( $ticket->provider_class );

			if ( empty( $provider ) || ! $provider instanceof Tribe__Tickets__Tickets ) {
				continue;
			}

			$duplicate_ticket_id = $provider->clone_ticket_to_new_post( $post->ID, $new_post_id, $ticket->ID );

			/**
			 * Fires after a ticket has been duplicated to a new post.
			 *
			 * @since 5.14.0
			 *
			 * @param int $duplicate_ticket_id The ID of the duplicated ticket.
			 * @param int $original_ticket_id The ID of the original ticket.
			 * @param int $new_post_id The ID of the new post.
			 * @param int $original_post_id The ID of the original post.
			 */
			do_action( 'tec_tickets_ticket_duplicated', $duplicate_ticket_id, $ticket->ID, $new_post_id, $post->ID );

			$duplicated_ticket_ids[ $ticket->ID ] = $duplicate_ticket_id;
		}

		/**
		 * Fires after all tickets have been duplicated to a new post.
		 *
		 * @since 5.14.0
		 *
		 * @param array $duplicated_ticket_ids An array of ticket IDs that were duplicated.
		 * @param int $new_post_id The ID of the new post.
		 * @param int $original_post_id The ID of the original post.
		 */
		do_action( 'tec_tickets_tickets_duplicated', $duplicated_ticket_ids, $new_post_id, $post->ID );
	}

	/**
	 * Fix post fields pointing to old tickets.
	 *
	 * @since 5.14.0
	 *
	 * @param array $duplicated_ticket_maps An array of ticket IDs that were duplicated.
	 * @param int   $new_post_id The ID of the new post.
	 * @param int   $original_post_id The ID of the original post.
	 *
	 * @return void
	 */
	public function remap_fields_pointing_to_old_tickets_after_duplicate( array $duplicated_ticket_maps, int $new_post_id, int $original_post_id ) {
		$duplicated_post = get_post( $new_post_id );
		if ( ! ( $duplicated_post && $duplicated_post->ID && $duplicated_post->post_content ) ) {
			return;
		}

		$tickets_block_locator = static fn( $v ) => '"ticketId":' . $v;

		$new_post_content = str_replace(
			array_map( $tickets_block_locator, array_keys( $duplicated_ticket_maps ) ),
			array_map( $tickets_block_locator, array_values( $duplicated_ticket_maps ) ),
			$duplicated_post->post_content
		);

		wp_update_post(
			[
				'ID'           => $new_post_id,
				'post_content' => $new_post_content,
			]
		);

		if ( ! get_post_meta( $original_post_id, Global_Stock::GLOBAL_STOCK_ENABLED, true ) ) {
			return;
		}

		update_post_meta(
			$new_post_id,
			Global_Stock::GLOBAL_STOCK_LEVEL,
			get_post_meta( $original_post_id, tribe( 'tickets.handler' )->key_capacity, true )
		);
	}

	/**
	 * Add tickets meta to the list of meta keys to duplicate.
	 *
	 * @since 5.14.0
	 *
	 * @param array $meta Meta keys to duplicate.
	 *
	 * @return array
	 */
	public function add_tickets_meta_to_duplicate( array $meta ): array {
		return array_merge(
			$meta,
			[
				tribe( 'tickets.handler' )->key_capacity,
				tribe( 'tickets.handler' )->key_image_header,
				tribe( 'tickets.handler' )->key_provider_field,
				Global_Stock::GLOBAL_STOCK_ENABLED,
				Attendees_List::HIDE_META_KEY,
			],
		);
	}
}
