<?php
/**
 * Limits the post an RSVP can be attached to when it is saved through the core REST API.
 *
 * @since 5.31.2
 *
 * @package TEC\Tickets\Blocks
 */

namespace TEC\Tickets\Blocks;

use stdClass;
use TEC\Tickets\Event;
use WP_Error;
use WP_Post;
use WP_REST_Request;

/**
 * Checks the parent post of an RSVP saved through the core REST API.
 *
 * @since 5.31.2
 *
 * @package TEC\Tickets\Blocks
 */
final class RSVP_Parent_Permission {
	/**
	 * Rejects a core REST create or update of an RSVP that names a parent post the current user cannot add it to.
	 *
	 * The meta `auth_callback` only receives the RSVP's own ID, never the submitted value, so the parent can only
	 * be checked here, before the RSVP is written.
	 *
	 * @since 5.31.2
	 *
	 * @param stdClass|WP_Error $prepared_post The post prepared for insertion, or an error from an earlier callback.
	 * @param WP_REST_Request   $request       The REST request creating or updating the RSVP.
	 *
	 * @return stdClass|WP_Error The prepared post, or an error when the parent post is not allowed.
	 */
	public function filter_rest_pre_insert( $prepared_post, $request ) {
		if ( is_wp_error( $prepared_post ) || ! $request instanceof WP_REST_Request ) {
			return $prepared_post;
		}

		$meta      = $request->get_param( 'meta' );
		$event_key = tribe( 'tickets.rsvp' )->get_event_key();

		// A `null` value deletes the meta, which attaches the RSVP to nothing.
		if ( ! is_array( $meta ) || ! isset( $meta[ $event_key ] ) ) {
			return $prepared_post;
		}

		if ( $this->can_attach_to( $meta[ $event_key ] ) ) {
			return $prepared_post;
		}

		return new WP_Error(
			'rest_forbidden',
			__( 'Sorry, you are not allowed to add an RSVP to this post.', 'event-tickets' ),
			[ 'status' => rest_authorization_required_code() ]
		);
	}

	/**
	 * Returns whether the current user can attach an RSVP to the post.
	 *
	 * @since 5.31.2
	 *
	 * @param mixed $parent_id The parent post ID as submitted in the request.
	 *
	 * @return bool Whether the parent is an existing ticket-enabled post the current user can edit.
	 */
	private function can_attach_to( $parent_id ): bool {
		$parent_id = filter_var( $parent_id, FILTER_VALIDATE_INT, [ 'options' => [ 'min_range' => 1 ] ] );
		$parent    = $parent_id ? get_post( Event::filter_event_id( $parent_id ) ) : null;

		return $parent instanceof WP_Post
			&& tribe_tickets_post_type_enabled( $parent->post_type )
			&& current_user_can( 'edit_post', $parent->ID );
	}
}
