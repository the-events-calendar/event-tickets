<?php
/**
 * Used for hooking into ECP to extend the functionality of duplicating events.
 */

namespace TEC\Tickets\Integrations\Plugins\Events_Pro;

use TEC\Tickets\Integrations\Integration_Abstract;
use TEC\Common\Integrations\Traits\Plugin_Integration;
use Tribe__Tickets__Tickets;

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

		// You technically can have multiple providers if you have RSVP + a ticket provider.
		foreach ( $tickets as $ticket ) {

			$provider = tribe( $ticket->provider_class );

			if ( empty( $provider ) || ! $provider instanceof Tribe__Tickets__Tickets ) {
				continue;
			}

			$provider->clone_ticket_to_new_post( $post->ID, $new_post_id, $ticket->ID );
		}
	}
}
