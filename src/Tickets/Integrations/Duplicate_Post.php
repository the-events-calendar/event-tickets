<?php

/**
 * Class Duplicate_Post
 *
 * Handles the duplication of tickets to new posts.
 */

namespace TEC\Tickets\Integrations;

use \TEC\Common\Contracts\Service_Provider;
use TEC\Tickets\Commerce\Module;
use Tribe__Tickets__Tickets;

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
		add_action( 'dp_duplicate_post', [ $this, 'duplicate_tickets_to_new_post' ], 10, 3 );
		add_action( 'dp_duplicate_page', [ $this, 'duplicate_tickets_to_new_post' ], 10, 3 );
	}

	/**
	 * Duplicate tickets to a new post.
	 *
	 * @param int    $new_post_id ID of the new post.
	 * @param object $post        Original post object.
	 * @param string $status      Duplicate status.
	 *
	 * @return int|WP_Error $new_post_id New post ID or WP_Error object on failure.
	 */
	public function duplicate_tickets_to_new_post( $new_post_id, $post, $status ) {
		/**
		 * @todo Fix this logic, maybe it will be possible to find the provider straight from the post instead of getting the first ticket the way I'm doing it now.
		 *  I should also try to figure out the best way for ET+ to hook into this for when woo/EDD is being used instead.
		 */
// For Tickets Commerce
		$ticket_ids = (array)tribe( Module::class )->get_tickets_ids( $post );
// For WooCommerce
		$ticket_ids = tribe( 'tickets-plus.commerce.woo' )->get_tickets_ids( $post->ID );
// For EDD
		$ticket_ids = tribe( 'tickets-plus.commerce.edd' )->get_tickets_ids( $post->ID );

		// If we have no tickets, return the new post ID.
		if ( empty( $ticket_ids ) ) {
			return $new_post_id;
		}

		// Since all providers for tickets should be the same, we check the first provider.
		$provider = tribe_tickets_get_ticket_provider( $ticket_ids[ 0 ] );




		if ( empty( $provider ) || ! $provider instanceof Tribe__Tickets__Tickets ) {
			return new WP_Error(
				'bad_request',
				__( 'Commerce Module invalid', 'event-tickets' ),
				[ 'status' => 400 ]
			);
		}

		$new_ticket_ids = [];

		foreach ( $ticket_ids as $ticket_id ) {
			$duplicate_ticket_id = $provider->clone_ticket_to_new_post( $post->ID, $new_post_id, $ticket_id );

			if ( false===$duplicate_ticket_id ) {
				// Handle failure to create duplicate ticket for this specific ticket.
				$new_ticket_ids[] = new WP_Error(
					'bad_request',
					__( 'Failed to create duplicate ticket for ticket ID: ', 'event-tickets' ) . $ticket_id,
					[ 'status' => 400 ]
				);
			} else {
				$new_ticket_ids[] = $duplicate_ticket_id;
			}
		}

		return $new_post_id;
	}
}