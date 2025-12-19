<?php
/**
 * V2 Ticket Repository for TC-RSVP tickets.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2\Repositories
 */

namespace TEC\Tickets\RSVP\V2\Repositories;

use TEC\Tickets\Commerce\Ticket;
use TEC\Tickets\Repositories\Traits\Get_Field;
use TEC\Tickets\RSVP\V2\Constants;
use Tribe__Repository;
use Tribe__Repository__Interface;
use WP_Post;

/**
 * Class Ticket_Repository
 *
 * Repository for querying TC-RSVP tickets.
 * Extends the base repository and automatically filters to only return tickets
 * with ticket_type = 'tc-rsvp'.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2\Repositories
 */
class Ticket_Repository extends Tribe__Repository {
	use Get_Field;

	/**
	 * The unique fragment that will be used to identify this repository filters.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected $filter_name = 'tc_rsvp_tickets';

	/**
	 * Constructor.
	 *
	 * @since TBD
	 */
	public function __construct() {
		parent::__construct();

		// Set the post type to TC tickets.
		$this->default_args['post_type']   = Ticket::POSTTYPE;
		$this->default_args['post_status'] = 'publish';
		$this->default_args['meta_query']  = [];

		// Always filter by the RSVP ticket type.
		$this->query_args['meta_query']['tc_rsvp_type'] = [
			'key'   => Ticket::$type_meta_key,
			'value' => Constants::TC_RSVP_TYPE,
		];

		// By default, order the Tickets by ID, ascending.
		$this->query_args['orderby'] = 'ID';
		$this->query_args['order']   = 'ASC';

		// Register schema filters.
		$this->schema['event'] = [ $this, 'filter_by_event' ];

		$this->add_simple_meta_schema_entry( 'event', Ticket::$event_relation_meta_key );
		$this->add_simple_meta_schema_entry( 'ticket_type', Ticket::$type_meta_key );
		$this->add_simple_meta_schema_entry( 'start_date', Ticket::START_DATE_META_KEY );
		$this->add_simple_meta_schema_entry( 'end_date', Ticket::END_DATE_META_KEY );
		$this->add_simple_meta_schema_entry( 'sku', Ticket::$sku_meta_key );
		$this->add_simple_meta_schema_entry( 'stock', Ticket::$stock_meta_key );
		$this->add_simple_meta_schema_entry( 'stock_mode', Ticket::$stock_mode_meta_key );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function format_item( $id ) {
		$formatted = null === $this->formatter
			? get_post( $id )
			: $this->formatter->format_item( $id );

		/**
		 * Filters a single formatted TC-RSVP ticket result.
		 *
		 * @since TBD
		 *
		 * @param mixed|WP_Post                $formatted  The formatted ticket result, usually a post object.
		 * @param int                          $id         The formatted post ID.
		 * @param Tribe__Repository__Interface $repository The current repository object.
		 */
		$formatted = apply_filters( 'tec_tickets_rsvp_v2_repository_ticket_format', $formatted, $id, $this );

		return $formatted;
	}

	/**
	 * Filters tickets by a specific event.
	 *
	 * @since TBD
	 *
	 * @param int|array $event_id The post ID or array of post IDs to filter by.
	 *
	 * @return void
	 */
	public function filter_by_event( $event_id ): void {
		/**
		 * Filters the post ID used to filter TC-RSVP tickets.
		 *
		 * @since TBD
		 *
		 * @param int|array         $event_id   The event ID or array of event IDs to filter by.
		 * @param Ticket_Repository $repository The current repository object.
		 */
		$event_id = apply_filters( 'tec_tickets_rsvp_v2_repository_filter_by_event_id', $event_id, $this );

		if ( is_array( $event_id ) && empty( $event_id ) ) {
			// Bail early if the array is empty.
			return;
		}

		if ( is_numeric( $event_id ) ) {
			$event_id = [ $event_id ];
		}

		$this->by( 'meta_in', Ticket::$event_relation_meta_key, $event_id );
	}

	/**
	 * Get the event ID for a ticket.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id Ticket ID.
	 *
	 * @return int|false Event ID or false if not found.
	 */
	public function get_event_id( int $ticket_id ) {
		$event_id = get_post_meta( $ticket_id, Ticket::$event_relation_meta_key, true );

		return $event_id ? (int) $event_id : false;
	}
}
