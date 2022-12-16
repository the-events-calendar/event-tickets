<?php
/**
 * Service for manipulating Meta Queries for Event Tickets + Custom Tables.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Custom_Tables\V1\WP_Query;
 */

namespace TEC\Tickets\Custom_Tables\V1\WP_Query;

use TEC\Events\Custom_Tables\V1\Models\Occurrence;
use TEC\Events_Pro\Custom_Tables\V1\Models\Provisional_Post;
use Tribe__Tickets__Attendee_Repository;
use Tribe__Tickets__Ticket_Repository;
use WP_Query;

/**
 * Service for manipulating Meta Queries for Event Tickets + Custom Tables.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Custom_Tables\V1\WP_Query;
 */
class WP_Meta_Query_Modifier {

	/**
	 * A reference to the Attendee Repository.
	 *
	 * @since TBD
	 *
	 * @var Tribe__Tickets__Attendee_Repository
	 */
	protected $attendee_repository;

	/**
	 * A reference to the Ticket Repository.
	 *
	 * @since TBD
	 *
	 * @var Tribe__Tickets__Ticket_Repository
	 */
	protected $ticket_repository;

	/**
	 * A list of meta keys to watch out for.
	 *
	 * @since TBD
	 *
	 * @var array<string>
	 */
	protected $meta_keys = [];

	/**
	 * The Provisional Post object.
	 *
	 * @since TBD
	 *
	 * @var Provisional_Post
	 */
	protected $provisional_post;

	/**
	 * Post types we are watching for.
	 *
	 * @since TBD
	 *
	 * @var array<string>
	 */
	protected $post_types = [];

	/**
	 * Ticket Meta Query Modifier constructor.
	 *
	 * @since TBD
	 *
	 * @param Tribe__Tickets__Attendee_Repository $attendee_repository
	 * @param Tribe__Tickets__Ticket_Repository   $ticket_repository
	 * @param Provisional_Post                    $provisional_post
	 */
	public function __construct(
		Tribe__Tickets__Attendee_Repository $attendee_repository,
		Tribe__Tickets__Ticket_Repository $ticket_repository,
		Provisional_Post $provisional_post
	) {
		$this->attendee_repository = $attendee_repository;
		$this->ticket_repository   = $ticket_repository;
		$this->meta_keys           = array_merge(
			array_values( $attendee_repository->attendee_to_event_keys() ),
			array_values( $ticket_repository->ticket_to_event_keys() )
		);
		$this->post_types          = array_merge(
			array_values( $ticket_repository->ticket_types() ),
			array_values( $attendee_repository->attendee_types() )
		);
		$this->provisional_post    = $provisional_post;
	}

	/**
	 * Detects and modifies WP_Query for ticket queries.
	 *
	 * @since TBD
	 *
	 * @param WP_Query $query The WP_Query fetching posts.
	 */
	public function modify_tickets_meta_query( $query ) {
		if ( ! $query instanceof WP_Query ) {
			return;
		}

		$post_types = array_values( (array) $query->get( 'post_type' ) );

		if ( ! count( array_intersect( $post_types, $this->post_types ) ) ) {
			return;
		}

		foreach ( $this->meta_keys as $prefix ) {
			// Which meta keys we should parse.
			$attempt_to_normalize_keys = [ $prefix . '_in', $prefix . '_not_in' ];
			foreach ( $attempt_to_normalize_keys as $meta_key ) {
				$original_value = $query->query_vars['meta_query'][ $meta_key ]['value'] ?? null;
				if ( $original_value === null ) {
					continue;
				}
				// Could be scalar or array, convert to array for single parsing method.
				$values = (array) $original_value;
				foreach ( $values as $i => $id ) {
					// Is this a Provisional ID that needs to be normalized to the Post ID?
					$values[ $i ] = $this->normalize_id( $id );
				}
				// Modify the original meta with the normalized values.
				$query->query_vars['meta_query'][ $meta_key ]['value'] =
					is_array( $original_value ) ? $values : reset( $values );
			}
		}
	}

	/**
	 * Non-destructive parser for normalizing provisional ID's to their Post ID.
	 *
	 * @since TBD
	 *
	 * @param mixed $id
	 *
	 * @return int|mixed
	 */
	public function normalize_id( $id ) {
		if ( is_numeric( $id ) && $this->provisional_post->is_provisional_post_id( $id ) ) {

			return Occurrence::normalize_id( $id );
		}

		return $id;
	}
}
