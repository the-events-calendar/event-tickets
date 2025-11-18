<?php
/**
 * Trait With_Parent_Post_Read_Check
 *
 * @since 5.26.0
 *
 * @package TEC\Tickets\REST\TEC\V1\Traits
 */

declare( strict_types=1 );

namespace TEC\Tickets\REST\TEC\V1\Traits;

use Tribe__Tickets__Tickets as Tickets;
use WP_REST_Request;
use WP_REST_Posts_Controller;

/**
 * Trait With_Parent_Post_Read_Check
 *
 * @since 5.26.0
 *
 * @package TEC\Tickets\REST\TEC\V1\Traits
 */
trait With_Parent_Post_Read_Check {
	/**
	 * Returns whether the user can read the ticket.
	 *
	 * @since 5.26.0
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return bool
	 */
	public function can_read( WP_REST_Request $request ): bool {
		$id = $request['id'] ?? null;

		// If requesting a specific post, validate status/visibility/password access.
		if ( $id ) {
			$post = get_post( (int) $id );
			if ( ! $post || $post->post_type !== $this->get_post_type() ) {
				return false;
			}

			$ticket_object = Tickets::load_ticket_object( (int) $id );

			$parent_post_id = $ticket_object->get_event_id();

			$endpoint_allowed = $this->guest_can_read() || current_user_can( get_post_type_object( get_post_type( $parent_post_id ) )->cap->read_post, (int) $parent_post_id );
			if ( ! $endpoint_allowed ) {
				return false;
			}

			$parent_post = get_post( $parent_post_id );

			$rest_controller = new WP_REST_Posts_Controller( $parent_post->post_type );

			if ( empty( $parent_post->post_password ) ) {
				return $rest_controller->check_read_permission( $parent_post );
			}

			return $rest_controller->can_access_password_content( $parent_post, $request ) && $rest_controller->check_read_permission( $parent_post );
		}

		// Collection/list requests: allow if endpoint is publicly readable or user has capability.
		return $this->guest_can_read() || current_user_can( get_post_type_object( $this->get_post_type() )->cap->read );
	}

	/**
	 * Formats a collection of posts into a collection of post entities.
	 *
	 * @since 5.26.0
	 * @since 5.27.0 method has been renamed.
	 *
	 * @param array $posts The posts to format.
	 *
	 * @return array
	 */
	protected function format_entity_collection( array $posts ): array {
		$formatted_posts = [];
		foreach ( $posts as $post ) {
			$ticket_object   = Tickets::load_ticket_object( (int) $post->ID );
			$parent_post_id  = $ticket_object->get_event_id();
			$parent_post     = get_post( $parent_post_id );
			$rest_controller = new WP_REST_Posts_Controller( $parent_post->post_type );

			if ( ! $rest_controller->check_read_permission( $parent_post ) ) {
				continue;
			}

			if ( ! empty( $parent_post->post_password ) && ! $rest_controller->can_access_password_content( $parent_post, $this->get_request() ) ) {
				continue;
			}

			$formatted_posts[] = $this->get_formatted_entity( $post );
		}

		return $formatted_posts;
	}
}
