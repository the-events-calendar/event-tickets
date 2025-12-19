<?php
/**
 * V2 Attendee Repository for TC-RSVP attendees.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2\Repositories
 */

namespace TEC\Tickets\RSVP\V2\Repositories;

use TEC\Tickets\Commerce\Attendee;
use TEC\Tickets\Event;
use TEC\Tickets\Repositories\Traits\Get_Field;
use TEC\Tickets\RSVP\Contracts\Attendee_Repository_Interface;
use Tribe__Repository;
use Tribe__Repository__Interface;
use WP_Post;

/**
 * Class Attendee_Repository
 *
 * Repository for querying TC-RSVP attendees.
 * Extends the base repository and provides filters for RSVP-specific queries
 * including going/not-going status.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2\Repositories
 */
class Attendee_Repository extends Tribe__Repository implements Attendee_Repository_Interface {
	use Get_Field;

	/**
	 * RSVP status meta key.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const RSVP_STATUS_META_KEY = '_tec_tickets_commerce_rsvp_status';

	/**
	 * The unique fragment that will be used to identify this repository filters.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected $filter_name = 'tc_rsvp_attendees';

	/**
	 * Constructor.
	 *
	 * @since TBD
	 */
	public function __construct() {
		parent::__construct();

		// Set the post type to TC attendees.
		$this->default_args['post_type']   = Attendee::POSTTYPE;
		$this->default_args['post_status'] = 'publish';

		// Set up schema for filtering.
		$this->schema = array_merge(
			$this->schema,
			[
				'event'     => [ $this, 'filter_by_event' ],
				'ticket'    => [ $this, 'filter_by_ticket' ],
				'going'     => [ $this, 'filter_by_going' ],
				'not_going' => [ $this, 'filter_by_not_going' ],
			]
		);

		// Add simple meta schema entries.
		$this->add_simple_meta_schema_entry( 'event_id', Attendee::$event_relation_meta_key );
		$this->add_simple_meta_schema_entry( 'ticket_id', Attendee::$ticket_relation_meta_key );
		$this->add_simple_meta_schema_entry( 'user_id', Attendee::$user_relation_meta_key );
		$this->add_simple_meta_schema_entry( 'rsvp_status', self::RSVP_STATUS_META_KEY );
		$this->add_simple_meta_schema_entry( 'checked_in', Attendee::$checked_in_meta_key );
		$this->add_simple_meta_schema_entry( 'full_name', Attendee::$full_name_meta_key );
		$this->add_simple_meta_schema_entry( 'email', Attendee::$email_meta_key );

		$this->update_fields_aliases = array_merge(
			$this->update_fields_aliases ?? [],
			[
				'full_name' => Attendee::$full_name_meta_key,
				'email'     => Attendee::$email_meta_key,
				'ticket_id' => Attendee::$ticket_relation_meta_key,
				'event_id'  => Attendee::$event_relation_meta_key,
			]
		);
	}

	/**
	 * {@inheritDoc}
	 */
	protected function format_item( $id ) {
		$formatted = null === $this->formatter
			? get_post( $id )
			: $this->formatter->format_item( $id );

		/**
		 * Filters a single formatted TC-RSVP attendee result.
		 *
		 * @since TBD
		 *
		 * @param mixed|WP_Post                $formatted  The formatted attendee result, usually a post object.
		 * @param int                          $id         The formatted post ID.
		 * @param Tribe__Repository__Interface $repository The current repository object.
		 */
		$formatted = apply_filters( 'tec_tickets_rsvp_v2_repository_attendee_format', $formatted, $id, $this );

		return $formatted;
	}

	/**
	 * Filters attendees by event.
	 *
	 * @since TBD
	 *
	 * @param int|array $event_id The event ID or array of event IDs.
	 *
	 * @return void
	 */
	public function filter_by_event( $event_id ): void {
		$event_ids = $this->clean_post_ids( $event_id );

		if ( empty( $event_ids ) ) {
			return;
		}

		$this->by( 'meta_in', Attendee::$event_relation_meta_key, $event_ids );
	}

	/**
	 * Filters attendees by ticket.
	 *
	 * @since TBD
	 *
	 * @param int|array $ticket_id The ticket ID or array of ticket IDs.
	 *
	 * @return void
	 */
	public function filter_by_ticket( $ticket_id ): void {
		$ticket_ids = $this->clean_post_ids( $ticket_id );

		if ( empty( $ticket_ids ) ) {
			return;
		}

		$this->by( 'meta_in', Attendee::$ticket_relation_meta_key, $ticket_ids );
	}

	/**
	 * Filters by going status.
	 *
	 * @since TBD
	 *
	 * @param bool $going Whether to filter by going (true) or not going (false).
	 *
	 * @return void
	 */
	public function filter_by_going( bool $going = true ): void {
		$this->by( 'rsvp_status', $going ? 'yes' : 'no' );
	}

	/**
	 * Filters by not going status.
	 *
	 * @since TBD
	 *
	 * @param bool $not_going Whether to filter by not going.
	 *
	 * @return void
	 */
	public function filter_by_not_going( bool $not_going = true ): void {
		$this->by( 'rsvp_status', $not_going ? 'no' : 'yes' );
	}

	/**
	 * Cleans up a list of Post IDs into an usable array for DB query.
	 *
	 * @since TBD
	 *
	 * @param int|WP_Post|int[]|WP_Post[] $posts Which posts we are filtering by.
	 *
	 * @return array
	 */
	protected function clean_post_ids( $posts ): array {
		return array_unique(
			array_filter(
				array_map(
					static function ( $post ) {
						$post = Event::filter_event_id( $post );

						if ( is_numeric( $post ) ) {
							return (int) $post;
						}

						if ( $post instanceof WP_Post ) {
							return $post->ID;
						}

						return null;
					},
					(array) $posts
				)
			)
		);
	}

	/**
	 * Get attendees by email address for privacy operations.
	 *
	 * @since TBD
	 *
	 * @param string $email    The email address to search for.
	 * @param int    $page     The page number (1-indexed).
	 * @param int    $per_page Number of results per page.
	 *
	 * @return array{posts: \WP_Post[], has_more: bool}
	 */
	public function get_attendees_by_email( string $email, int $page, int $per_page ): array {
		$posts = $this->by( 'email', $email )
						->by( 'meta_exists', self::RSVP_STATUS_META_KEY )
						->per_page( $per_page )
						->page( $page )
						->order_by( 'ID' )
						->order( 'ASC' )
						->all();

		return [
			'posts'    => $posts,
			'has_more' => count( $posts ) >= $per_page,
		];
	}

	/**
	 * Delete an attendee for privacy erasure.
	 *
	 * Uses force delete (bypass trash) to ensure complete removal of personal data
	 * as required for GDPR compliance.
	 *
	 * @since TBD
	 *
	 * @param int $attendee_id The attendee post ID to delete.
	 *
	 * @return array{success: bool, event_id: int|null}
	 */
	public function delete_attendee( int $attendee_id ): array {
		$event_id = get_post_meta( $attendee_id, Attendee::$event_relation_meta_key, true );
		$deleted  = wp_delete_post( $attendee_id, true );

		return [
			'success'  => (bool) $deleted,
			'event_id' => $event_id ? (int) $event_id : null,
		];
	}

	/**
	 * Get the ticket/product ID for an attendee.
	 *
	 * @since TBD
	 *
	 * @param int $attendee_id The attendee post ID.
	 *
	 * @return int The ticket/product ID, or 0 if not found.
	 */
	public function get_ticket_id( int $attendee_id ): int {
		$ticket_id = get_post_meta( $attendee_id, Attendee::$ticket_relation_meta_key, true );

		return $ticket_id ? (int) $ticket_id : 0;
	}
}
