<?php

namespace TEC\Tickets\Integrations\Plugins\Yoast_Duplicate_Post;

use TEC\Tickets\Integrations\Integration_Abstract;
use TEC\Common\Integrations\Traits\Plugin_Integration;
use Tribe__Tickets__Tickets;
use WP_Error;

/**
 * Class Duplicate_Post
 *
 * Extends the cloning capability introduced by Yoast Duplicate Post plugin to also handle the duplication of tickets
 * to new posts.
 *
 * @since 5.6.3
 *
 * @package TEC\Events\Integrations\Plugins\Yoast_Duplicate_Post
 */
class Duplicate_Post extends Integration_Abstract {

	use Plugin_Integration;


	/**
	 * @inheritDoc
	 */
	public static function get_slug(): string {
		return 'yoast-duplicate-post';
	}

	/**
	 * @inheritDoc
	 *
	 * @return bool Whether or not integrations should load.
	 */
	public function load_conditionals(): bool {
		return defined( 'DUPLICATE_POST_FILE' ) && ! empty( DUPLICATE_POST_FILE );
	}

	/**
	 * @inheritDoc
	 */
	protected function load(): void {
		add_action( 'dp_duplicate_post', [ $this, 'duplicate_tickets_to_new_post' ], 10, 2 );
		add_action( 'dp_duplicate_page', [ $this, 'duplicate_tickets_to_new_post' ], 10, 2 );
	}

	/**
	 * Duplicate tickets to a new post.
	 *
	 * @since 5.6.3
	 *
	 * @param int    $new_post_id ID of the new post.
	 * @param object $post        Original post object.
	 *
	 * @return int|WP_Error $new_post_id New post ID or WP_Error object on failure.
	 */
	public function duplicate_tickets_to_new_post( $new_post_id, $post ) {

		$tickets = Tribe__Tickets__Tickets::get_all_event_tickets( $post->ID );

		// If we have no tickets, return the new post ID.
		if ( empty( $tickets ) ) {
			return $new_post_id;
		}

		foreach ( $tickets as $ticket ) {

			$provider = tribe( $ticket->provider_class );

			if ( empty( $provider ) || ! $provider instanceof Tribe__Tickets__Tickets ) {
				return new WP_Error(
					'bad_request',
					__( 'Commerce Module invalid', 'event-tickets' ),
					[ 'status' => 400 ]
				);
			}

			$provider->clone_ticket_to_new_post( $post->ID, $new_post_id, $ticket->ID );
		}

		return $new_post_id;
	}
}
