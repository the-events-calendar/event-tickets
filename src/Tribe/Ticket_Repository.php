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
		$this->schema = array_merge( $this->schema, array(
			'event'             => array( $this, 'filter_by_event' ),
			'event_not_in'      => array( $this, 'filter_by_event_not_in' ),
			'is_available'      => array( $this, 'filter_by_availability' ),
			'provider'          => array( $this, 'filter_by_provider' ),
			'attendees_min'     => array( $this, 'filter_by_attendees_min' ),
			'attendees_max'     => array( $this, 'filter_by_attendees_max' ),
			'attendees_between' => array( $this, 'filter_by_attendees_between' ),
			'checkedin_min'     => array( $this, 'filter_by_checkedin_min' ),
			'checkedin_max'     => array( $this, 'filter_by_checkedin_max' ),
			'checkedin_between' => array( $this, 'filter_by_checkedin_between' ),
			'capacity_min'      => array( $this, 'filter_by_capacity_min' ),
			'capacity_max'      => array( $this, 'filter_by_capacity_max' ),
			'capacity_between'  => array( $this, 'filter_by_capacity_between' ),
			'available_from'    => array( $this, 'filter_by_available_from' ),
			'available_until'   => array( $this, 'filter_by_available_until' ),
			'event_status'      => array( $this, 'filter_by_event_status' ),
			'has_attendee_meta' => array( $this, 'filter_by_attendee_meta_existence' ),
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

	/**
	 * Filters tickets by a minimum capacity.
	 *
	 * @since TBD
	 *
	 * @param int $capacity_min
	 */
	public function filter_by_capacity_min( $capacity_min ) {
		/**
		 * Tickets with unlimited capacity will have a `_capacity` meta of `-1`
		 * but they will always satisfy any minimum capacity requirement
		 * so we need to use a custom query.
		 */

		/** @var wpdb $wpdb */
		global $wpdb;

		$min = $this->prepare_value( $capacity_min, '%d' );
		$this->join_clause( "JOIN {$wpdb->postmeta} capacity_min ON {$wpdb->posts}.ID = capacity_min.post_id" );
		$this->where_clause( "capacity_min.meta_key = '_capacity' AND (capacity_min.meta_value >= {$min} OR capacity_min.meta_value < 0)" );
	}

	/**
	 * Filters tickets by a maximum capacity.
	 *
	 * @since TBD
	 *
	 * @param int $capacity_max
	 */
	public function filter_by_capacity_max( $capacity_max ) {
		/**
		 * Tickets with unlimited capacity will have a `_capacity` meta of `-1`
		 * but they should not satisfy any maximum capacity requirement
		 * so we need to use a BETWEEN query.
		 */
		$this->by( 'meta_between', '_capacity', array( 0, $capacity_max ), 'NUMERIC' );
	}

	/**
	 * Filters tickets by a minimum and maximum capacity.
	 *
	 * @since TBD
	 *
	 * @param int $capacity_min
	 * @param int $capacity_max
	 */
	public function filter_by_capacity_between( $capacity_min, $capacity_max ) {
		$this->by( 'meta_between', '_capacity', array( (int) $capacity_min, (int) $capacity_max ), 'NUMERIC' );
	}

	/**
	 * Filters tickets by their available date being starting on a date.
	 *
	 * @since TBD
	 *
	 * @param string|int $date
	 *
	 * @return array
	 */
	public function filter_by_available_from( $date) {
		// the input is a UTC date or timestamp
		$utc_date_string = is_numeric( $date ) ? "@{$date}" : $date;
		$utc_date        = new DateTime( $utc_date_string, new DateTimeZone( 'UTC' ) );
		$from            = Tribe__Timezones::to_tz( $utc_date->format( 'Y-m-d H:i:s' ), Tribe__Timezones::wp_timezone_string() );

		return array(
			'meta_query' => array(
				'available-from' => array(
					'not-exists' => array(
						'key'     => '_ticket_start_date',
						'compare' => 'NOT EXISTS',
					),
					'relation'   => 'OR',
					'from'       => array(
						'key'     => '_ticket_start_date',
						'compare' => '>=',
						'value'   => $from,
					),
				),
			),
		);
	}

	/**
	 * Filters tickets by their available date being until a date.
	 *
	 * @since TBD
	 *
	 * @param string|int $date
	 *
	 * @return array
	 */
	public function filter_by_available_until( $date ) {
		// the input is a UTC date or timestamp
		$utc_date_string = is_numeric( $date ) ? "@{$date}" : $date;
		$utc_date        = new DateTime( $utc_date_string, new DateTimeZone( 'UTC' ) );
		$until           = Tribe__Timezones::to_tz( $utc_date->format( 'Y-m-d H:i:s' ), Tribe__Timezones::wp_timezone_string() );

		return array(
			'meta_query' => array(
				'available-until' => array(
					'not-exists' => array(
						'key'     => '_ticket_end_date',
						'compare' => 'NOT EXISTS',
					),
					'relation'   => 'OR',
					'from'       => array(
						'key'     => '_ticket_end_date',
						'compare' => '<=',
						'value'   => $until,
					),
				),
			),
		);
	}

	/**
	 * Filters tickets to only get those related to posts with a specific status.
	 *
	 * @since TBD
	 *
	 * @param string|array $event_status
	 *
	 * @throws Tribe__Repository__Void_Query_Exception If the requested statuses are not accessible by the user.
	 * @throws Tribe__Repository__Usage_Error
	 */
	public function filter_by_event_status( $event_status ) {
		$statuses = Tribe__Utils__Array::list_to_array( $event_status );

		$can_read_private_posts = current_user_can( 'read_private_posts' );

		// map the `any` meta-status
		if ( 1 === count( $statuses ) && 'any' === $statuses[0] ) {
			if ( ! $can_read_private_posts ) {
				$statuses = array( 'publish' );
			} else {
				// no need to filter if the user can read all posts
				return;
			}
		}

		if ( ! $can_read_private_posts ) {
			$event_status = array_intersect( $statuses, array( 'publish' ) );
		}

		if ( empty( $event_status ) ) {
			throw Tribe__Repository__Void_Query_Exception::because_the_query_would_yield_no_results(
				'The user cannot read posts with the requested post statuses.'
			);
		}

		$this->where_meta_related_by(
			$this->ticket_to_event_keys(),
			'IN',
			'post_status',
			$statuses
		);
	}

	/**
	 * Filters tickets depending on them having additional
	 * information available and active or not.
	 *
	 * @since TBD
	 *
	 * @param bool $exists
	 *
	 * @return array
	 */
	public function filter_by_attendee_meta_existence( $exists ) {
		if ( ! class_exists( 'Tribe__Tickets_Plus__Meta' ) ) {
			return;
		}

		if ( $exists ) {
			return array(
				'meta_query' => array(
					'by-attendee-meta-availability' => array(
						'is-enabled' => array(
							'key'     => Tribe__Tickets_Plus__Meta::ENABLE_META_KEY,
							'compare' => '=',
							'value'   => 'yes',
						),
						'relation'   => 'AND',
						'has-meta'   => array(
							'key'     => Tribe__Tickets_Plus__Meta::META_KEY,
							'compare' => 'EXISTS'
						),
					),
				),
			);
		}

		return array(
			'meta_query' => array(
				'by-attendee-meta-availability' => array(
					'is-not-enabled' => array(
						'key'     => Tribe__Tickets_Plus__Meta::ENABLE_META_KEY,
						'compare' => '!=',
						'value'   => 'yes',
					),
					'relation'       => 'OR',
					'not-exists'     => array(
						'key'     => Tribe__Tickets_Plus__Meta::ENABLE_META_KEY,
						'compare' => 'NOT EXISTS'
					),
				),
			),
		);
	}
}
