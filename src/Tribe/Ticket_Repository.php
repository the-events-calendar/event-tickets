<?php

/**
 * Class Tribe__Tickets__Ticket_Repository
 *
 * The basic ticket repository.
 *
 * @since TBD
 */
class Tribe__Tickets__Ticket_Repository extends Tribe__Repository {


	/**
	 * Tribe__Tickets__Ticket_Repository constructor.
	 *
	 * @since TBD
	 */
	public function __construct() {
		parent::__construct();
		$this->default_args = array(
			'post_type' => $this->ticket_types(),
			'orderby'   => array( 'date', 'ID' ),
		);
		$this->schema       = array_merge( $this->schema, array(
			'event'             => array( $this, 'filter_by_event' ),
			'event_not_in'      => array( $this, 'filter_by_event_not_in' ),
			'is_available'      => array( $this, 'filter_by_availability' ),
			'provider'          => array( $this, 'filter_by_provider' ),
			'attendees_min'     => array( $this, 'filter_by_attendees_min' ),
			'attendees_max'     => array( $this, 'filter_by_attendees_max' ),
			'attendees_between' => array( $this, 'filter_by_attendees_between' ),
			'checkedin_min'     => array( $this, 'filter_by_checkedin_min' ),
			'checkedin_max'     => array( $this, 'filter_by_checkedin_max' ),
			'checkedin_between' => array( $this, 'filter_by_checkedin_between' )
		) );
	}

	/**
	 * Returns an array of the ticket types handled by this repository.
	 *
	 * Extending repository classes should override this to add more ticket types.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public function ticket_types() {
		return array( 'tribe_rsvp_tickets', 'tribe_tpp_tickets' );
	}

	/**
	 * Filters tickets by a specific event.
	 *
	 * @since TBD
	 *
	 * @param int|array $event_id
	 */
	public function filter_by_event( $event_id ) {
		$this->by( 'meta_in', $this->ticket_to_event_keys(), $event_id );
	}

	/**
	 * Returns the list of meta keys relating a Ticket to a Post (Event).
	 *
	 * Extending repository classes should override this to add more keys.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public function ticket_to_event_keys() {
		return array(
			'rsvp'           => '_tribe_rsvp_for_event',
			'tribe-commerce' => '_tribe_tpp_for_event',
		);
	}

	/**
	 * Filters tickets by not being related to a specific event.
	 *
	 * @since TBD
	 *
	 * @param int|array $event_id
	 */
	public function filter_by_event_not_in( $event_id ) {
		$this->by( 'meta_not_in', $this->ticket_to_event_keys(), $event_id );
	}

	/**
	 * Sets up the query to filter tickets by availability.
	 *
	 * @since TBD
	 *
	 * @param bool $is_available
	 */
	public function filter_by_availability( $is_available ) {
		$want_available = (bool) $is_available;

		if ( $want_available ) {
			$this->where( 'meta_gt', '_capacity', 0 );
		} else {
			$this->where( 'meta_equals', '_capacity', 0 );
		}
	}

	/**
	 * Sets up the query to filter tickets by provider.
	 *
	 * @since TBD
	 *
	 * @param string|array $provider
	 */
	public function filter_by_provider( $provider ) {
		$providers = Tribe__Utils__Array::list_to_array( $provider );
		$meta_keys = Tribe__Utils__Array::map_or_discard( (array) $providers, $this->ticket_to_event_keys() );

		$this->by( 'meta_exists', $meta_keys );
	}

	/**
	 * Adds a WHERE clause to the query to filter tickets that have a minimum
	 * number of attendees.
	 *
	 * @since TBD
	 *
	 * @param int $attendees_min
	 */
	public function filter_by_attendees_min( $attendees_min ) {
		$this->by_related_to_min( tribe_attendees()->attendee_to_ticket_keys(), $attendees_min );
	}

	/**
	 * Adds a WHERE clause to the query to filter tickets that have a maximum
	 * number of attendees.
	 *
	 * @since TBD
	 *
	 * @param int $attendees_max
	 */
	public function filter_by_attendees_max( $attendees_max ) {
		$this->by_related_to_max( tribe_attendees()->attendee_to_ticket_keys(), $attendees_max );
	}

	/**
	 * Adds a WHERE clause to the query to filter tickets that have a number
	 * of attendees between two values.
	 *
	 * @since TBD
	 *
	 * @param int $attendees_min
	 * @param int $attendees_max
	 */
	public function filter_by_attendees_between( $attendees_min, $attendees_max ) {
		$this->by_related_to_between( tribe_attendees()->attendee_to_ticket_keys(), $attendees_min, $attendees_max );
	}

	/**
	 * Adds a WHERE clause to the query to filter tickets that have a minimum
	 * number of checked-in attendees.
	 *
	 * @since TBD
	 *
	 * @param int $checkedin_min
	 */
	public function filter_by_checkedin_min( $checkedin_min ) {
		$this->by_related_to_min(
			tribe_attendees()->attendee_to_ticket_keys(),
			$checkedin_min,
			tribe_attendees()->checked_in_keys(),
			'1'
		);
	}

	/**
	 * Adds a WHERE clause to the query to filter tickets that have a maximum
	 * number of checked-in attendees.
	 *
	 * @since TBD
	 *
	 * @param int $checkedin_max
	 */
	public function filter_by_checkedin_max( $checkedin_max ) {
		$this->by_related_to_max(
			tribe_attendees()->attendee_to_ticket_keys(),
			$checkedin_max,
			tribe_attendees()->checked_in_keys(),
			'1'
		);
	}

	/**
	 * Adds a WHERE clause to the query to filter tickets that have a number
	 * of checked-in attendees between two values.
	 *
	 * @since TBD
	 *
	 * @param int $checkedin_min
	 * @param int $checkedin_max
	 */
	public function filter_by_checkedin_between( $checkedin_min, $checkedin_max ) {
		$this->by_related_to_between(
			tribe_attendees()->attendee_to_ticket_keys(),
			$checkedin_min,
			$checkedin_max,
			tribe_attendees()->checked_in_keys(),
			'1'
		);
	}
}
