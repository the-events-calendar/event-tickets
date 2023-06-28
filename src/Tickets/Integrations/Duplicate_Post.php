<?php

/**
 * Class Duplicate_Post
 *
 * Handles the duplication of tickets to new posts.
 */

namespace TEC\Tickets\Integrations;

use \TEC\Common\Contracts\Service_Provider;
use Tribe__Tickets__Tickets;
use WP_Error;

class Duplicate_Post extends Service_Provider {

	/**
	 * Binds and sets up implementations.
	 *
	 * @since TBD
	 */
	public function register() {
		$this->hooks();
	}

	/**
	 * Hook into actions for duplicating posts and pages.
	 *
	 * @since TBD
	 */
	public function hooks() {
		add_action( 'dp_duplicate_post', [ $this, 'duplicate_tickets_to_new_post' ], 10, 2 );
		add_action( 'dp_duplicate_page', [ $this, 'duplicate_tickets_to_new_post' ], 10, 2 );
	}

	/**
	 * Duplicate tickets to a new post.
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

		$provider = tribe( $tickets[ 0 ]->provider_class );

		if ( empty( $provider ) || ! $provider instanceof Tribe__Tickets__Tickets ) {
			return new WP_Error(
				'bad_request',
				__( 'Commerce Module invalid', 'event-tickets' ),
				[ 'status' => 400 ]
			);
		}


		foreach ( $tickets as $ticket ) {
			$provider->clone_ticket_to_new_post( $post->ID, $new_post_id, $ticket->ID );
		}

		return $new_post_id;
	}
}