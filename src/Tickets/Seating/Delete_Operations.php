<?php
/**
 * The controller that manages relationships between entities in the Seating context.
 *
 * @since 5.16.0
 *
 * @package TEC\Tickets\Seating;
 */

namespace TEC\Tickets\Seating;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use Tribe__Tickets__Tickets as Tickets;
use WP_Post;

/**
 * Class Delete_Operations.
 *
 * @since 5.16.0
 *
 * @package TEC\Tickets\Seating;
 */
class Delete_Operations extends Controller_Contract {
	/**
	 * Register the controller by subscribing to WordPress hooks and binding implementations.
	 *
	 * @since 5.16.0
	 *
	 * @return void
	 */
	protected function do_register(): void {
		add_action( 'trashed_post', [ $this, 'remove_asc_flag' ] );
		add_action( 'before_delete_post', [ $this, 'remove_asc_flag' ], 100, 2 );
	}

	/**
	 * Unregister the controller by unsubscribing from WordPress hooks.
	 *
	 * @since 5.16.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'trashed_post', [ $this, 'remove_asc_flag' ] );
		remove_action( 'before_delete_post', [ $this, 'remove_asc_flag' ], 100, 2 );
	}

	/**
	 * Removes the Seating flags from a post when the last ASC ticket is deleted or trashed.
	 *
	 * @since 5.16.0
	 *
	 * @param int          $ticket_id   The (candidate) ticket ID.
	 * @param WP_Post|null $ticket_post The (candidate) ticket post object.
	 *
	 * @return void The ASC flag is removed from the post if the delete/trashed post is the last ASC ticket
	 *              associated with the post.
	 */
	public function remove_asc_flag( $ticket_id, $ticket_post = null ): void {
		$ticket_types = tribe_tickets()->ticket_types();
		$post_type    = $ticket_post instanceof WP_Post ? $ticket_post->post_type : get_post_type( $ticket_id );

		if ( ! in_array( $post_type, $ticket_types, true ) ) {
			return;
		}

		$seat_type = get_post_meta( $ticket_id, Meta::META_KEY_SEAT_TYPE, true );

		if ( ! $seat_type ) {
			return;
		}

		$ticket_object = Tickets::load_ticket_object( $ticket_id );

		if ( ! $ticket_object instanceof Ticket_Object ) {
			return;
		}

		$post_id = $ticket_object->get_event_id();

		if ( ! $post_id ) {
			return;
		}

		$has_other_tickets = tribe_tickets()->where( 'event', $post_id )
		                                    ->not_in( $ticket_id )
		                                    ->where( 'meta_exists', Meta::META_KEY_SEAT_TYPE )
		                                    ->count() > 0;

		if ( $has_other_tickets ) {
			return;
		}

		delete_post_meta( $post_id, Meta::META_KEY_ENABLED );
		delete_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID );
	}
}
