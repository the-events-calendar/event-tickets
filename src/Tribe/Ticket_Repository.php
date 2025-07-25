<?php

use TEC\Common\StellarWP\DB\DB;
use TEC\Tickets\Commerce\Repositories\Tickets_Repository;
use TEC\Tickets\Event;
use Tribe__Tickets__Global_Stock as Global_Stock;
use Tribe__Tickets__Tickets_Handler as Tickets_Handler;

/**
 * Class Tribe__Tickets__Ticket_Repository
 *
 * The basic ticket repository.
 *
 * @since 4.8
 */
class Tribe__Tickets__Ticket_Repository extends Tribe__Repository {

	/**
	 * The unique fragment that will be used to identify this repository filters.
	 *
	 * @var string
	 */
	protected $filter_name = 'tickets';

	/**
	 * Tribe__Tickets__Ticket_Repository constructor.
	 *
	 * @since 4.8
	 */
	public function __construct() {
		parent::__construct();

		$this->create_args['post_type'] = current( $this->ticket_types() );

		$this->default_args = [
			'post_type' => $this->ticket_types(),
			'orderby'   => [ 'date', 'ID' ],
		];

		$this->schema = array_merge( $this->schema, [
			'event'             => [ $this, 'filter_by_event' ],
			'event_not_in'      => [ $this, 'filter_by_event_not_in' ],
			'is_available'      => [ $this, 'filter_by_availability' ],
			'provider'          => [ $this, 'filter_by_provider' ],
			'attendees_min'     => [ $this, 'filter_by_attendees_min' ],
			'attendees_max'     => [ $this, 'filter_by_attendees_max' ],
			'attendees_between' => [ $this, 'filter_by_attendees_between' ],
			'checkedin_max'     => [ $this, 'filter_by_checkedin_max' ],
			'checkedin_min'     => [ $this, 'filter_by_checkedin_min' ],
			'checkedin_between' => [ $this, 'filter_by_checkedin_between' ],
			'capacity_min'      => [ $this, 'filter_by_capacity_min' ],
			'capacity_max'      => [ $this, 'filter_by_capacity_max' ],
			'capacity_between'  => [ $this, 'filter_by_capacity_between' ],
			'available_from'    => [ $this, 'filter_by_available_from' ],
			'available_until'   => [ $this, 'filter_by_available_until' ],
			'event_status'      => [ $this, 'filter_by_event_status' ],
			'has_attendee_meta' => [ $this, 'filter_by_attendee_meta_existence' ],
			'currency_code'     => [ $this, 'filter_by_currency_code' ],
			'is_active'         => [ $this, 'filter_by_active' ],
			'type'              => [ $this, 'filter_by_type' ],
			'type__not_in'      => [ $this, 'filter_by_type_not_in' ],
			'global_stock_mode' => [ $this, 'filter_by_global_stock_mode' ]
		] );
	}

	/**
	 * Returns an array of the ticket types handled by this repository.
	 *
	 * Extending repository classes should override this to add more ticket types.
	 *
	 * @since 4.8
	 *
	 * @return array
	 */
	public function ticket_types() {
		return [
			'rsvp'                         => 'tribe_rsvp_tickets',
			'tribe-commerce'               => 'tribe_tpp_tickets',
			TEC\Tickets\Commerce::PROVIDER => TEC\Tickets\Commerce\Ticket::POSTTYPE,
		];
	}

	/**
	 * Filters tickets by a specific event.
	 *
	 * @since 4.8
	 * @since 5.8.0 Apply the `tec_tickets_repository_filter_by_event_id` filter.
	 *
	 * @param int|array $event_id The post ID or array of post IDs to filter by.
	 */
	public function filter_by_event( $event_id ) {
		if ( is_array( $event_id ) ) {
			foreach ( $event_id as $key => $id ) {
				$event_id[ $key ] = Event::filter_event_id( $id );
			}
		} else {
			$event_id = Event::filter_event_id( $event_id );
		}

		/**
		 * Filters the post ID used to filter tickets.
		 *
		 * By default, only the ticketed post ID is used. This filter allows fetching tickets from related posts.
		 *
		 * @since 5.8.0
		 *
		 * @param int|array          $event_id   The event ID or array of event IDs to filter by.
		 * @param Tickets_Repository $repository The current repository object.
		 */
		$event_id = apply_filters( 'tec_tickets_repository_filter_by_event_id', $event_id, $this );

		if ( is_array( $event_id ) && empty( $event_id ) ) {
			// Bail early if the array is empty.
			return;
		}

		if ( is_numeric( $event_id ) ) {
			$event_id = [ $event_id ];
		}

		$this->by( 'meta_in', $this->ticket_to_event_keys(), $event_id );
	}

	/**
	 * Returns the list of meta keys relating a Ticket to a Post (Event).
	 *
	 * Extending repository classes should override this to add more keys.
	 *
	 * @since 4.8
	 *
	 * @return array
	 */
	public function ticket_to_event_keys() {
		return [
			'rsvp'                         => '_tribe_rsvp_for_event',
			'tribe-commerce'               => '_tribe_tpp_for_event',
			TEC\Tickets\Commerce::PROVIDER => TEC\Tickets\Commerce\Ticket::$event_relation_meta_key,
		];
	}

	/**
	 * Filters tickets by not being related to a specific event.
	 *
	 * @since 4.8
	 *
	 * @param int|array $event_id
	 */
	public function filter_by_event_not_in( $event_id ) {
		if ( is_array( $event_id ) ) {
			foreach ( $event_id as $key => $id ) {
				$event_id[ $key ] = Event::filter_event_id( $id );
			}
		} else {
			$event_id = Event::filter_event_id( $event_id );
		}
		$this->by( 'meta_not_in', $this->ticket_to_event_keys(), $event_id );
	}

	/**
	 * Sets up the query to filter tickets by availability.
	 *
	 * @since 4.8
	 *
	 * @param bool $is_available
	 */
	public function filter_by_availability( $is_available ) {
		$want_available = (bool) $is_available;

		/** @var Tribe__Tickets__Tickets_Handler $tickets_handler */
		$tickets_handler   = tribe( 'tickets.handler' );
		$capacity_meta_key = $tickets_handler->key_capacity;

		if ( $want_available ) {
			$this->where( 'meta_gt', $capacity_meta_key, 0 );
		} else {
			$this->where( 'meta_equals', $capacity_meta_key, 0 );
		}
	}

	/**
	 * Sets up the query to filter tickets by provider.
	 *
	 * @since 4.8
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
	 * @since 4.8
	 *
	 * @param int $attendees_min
	 */
	public function filter_by_attendees_min( $attendees_min ) {
		/** @var Tribe__Tickets__Attendee_Repository $attendees */
		$attendees = tribe_attendees();

		$this->by_related_to_min( $attendees->attendee_to_ticket_keys(), $attendees_min );
	}

	/**
	 * Adds a WHERE clause to the query to filter tickets that have a maximum
	 * number of attendees.
	 *
	 * @since 4.8
	 *
	 * @param int $attendees_max
	 */
	public function filter_by_attendees_max( $attendees_max ) {
		/** @var Tribe__Tickets__Attendee_Repository $attendees */
		$attendees = tribe_attendees();

		$this->by_related_to_max( $attendees->attendee_to_ticket_keys(), $attendees_max );
	}

	/**
	 * Adds a WHERE clause to the query to filter tickets that have a number
	 * of attendees between two values.
	 *
	 * @since 4.8
	 *
	 * @param int $attendees_min
	 * @param int $attendees_max
	 */
	public function filter_by_attendees_between( $attendees_min, $attendees_max ) {
		/** @var Tribe__Tickets__Attendee_Repository $attendees */
		$attendees = tribe_attendees();

		$this->by_related_to_between( $attendees->attendee_to_ticket_keys(), $attendees_min, $attendees_max );
	}

	/**
	 * Adds a WHERE clause to the query to filter tickets that have a minimum
	 * number of checked-in attendees.
	 *
	 * @since 4.8
	 *
	 * @param int $checkedin_min
	 */
	public function filter_by_checkedin_min( $checkedin_min ) {
		/** @var Tribe__Tickets__Attendee_Repository $attendees */
		$attendees = tribe_attendees();

		$this->by_related_to_min( $attendees->attendee_to_ticket_keys(), $checkedin_min, $attendees->checked_in_keys(), '1' );
	}

	/**
	 * Adds a WHERE clause to the query to filter tickets that have a maximum
	 * number of checked-in attendees.
	 *
	 * @since 4.8
	 *
	 * @param int $checkedin_max
	 */
	public function filter_by_checkedin_max( $checkedin_max ) {
		/** @var Tribe__Tickets__Attendee_Repository $attendees */
		$attendees = tribe_attendees();

		$this->by_related_to_max( $attendees->attendee_to_ticket_keys(), $checkedin_max, $attendees->checked_in_keys(), '1' );
	}

	/**
	 * Adds a WHERE clause to the query to filter tickets that have a number
	 * of checked-in attendees between two values.
	 *
	 * @since 4.8
	 *
	 * @param int $checkedin_min
	 * @param int $checkedin_max
	 */
	public function filter_by_checkedin_between( $checkedin_min, $checkedin_max ) {
		/** @var Tribe__Tickets__Attendee_Repository $attendees */
		$attendees = tribe_attendees();

		$this->by_related_to_between( $attendees->attendee_to_ticket_keys(), $checkedin_min, $checkedin_max, $attendees->checked_in_keys(), '1' );
	}

	/**
	 * Filters tickets by a minimum capacity.
	 *
	 * @since 4.8
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

		/** @var Tribe__Tickets__Tickets_Handler $tickets_handler */
		$tickets_handler = tribe( 'tickets.handler' );

		$capacity_meta_key = $this->prepare_value( $tickets_handler->key_capacity, '%s' );
		$this->where_clause( "capacity_min.meta_key = {$capacity_meta_key} AND (capacity_min.meta_value >= {$min} OR capacity_min.meta_value < 0)" );
	}

	/**
	 * Filters tickets by a maximum capacity.
	 *
	 * @since 4.8
	 *
	 * @param int $capacity_max
	 */
	public function filter_by_capacity_max( $capacity_max ) {
		/** @var Tribe__Tickets__Tickets_Handler $tickets_handler */
		$tickets_handler = tribe( 'tickets.handler' );

		/**
		 * Tickets with unlimited capacity will have a `_capacity` meta of `-1`
		 * but they should not satisfy any maximum capacity requirement
		 * so we need to use a BETWEEN query.
		 */
		$this->by( 'meta_between', $tickets_handler->key_capacity, [ 0, $capacity_max ], 'NUMERIC' );
	}

	/**
	 * Filters tickets by a minimum and maximum capacity.
	 *
	 * @since 4.8
	 *
	 * @param int $capacity_min
	 * @param int $capacity_max
	 */
	public function filter_by_capacity_between( $capacity_min, $capacity_max ) {
		/** @var Tribe__Tickets__Tickets_Handler $tickets_handler */
		$tickets_handler = tribe( 'tickets.handler' );

		$this->by( 'meta_between', $tickets_handler->key_capacity, [
			(int) $capacity_min,
			(int) $capacity_max,
		], 'NUMERIC' );
	}

	/**
	 * Filters tickets by their available date being starting on a date.
	 *
	 * @since 4.8
	 *
	 * @param string|int $date
	 *
	 * @return array
	 * @throws Exception
	 *
	 */
	public function filter_by_available_from( $date ) {
		// the input is a UTC date or timestamp
		$utc_date_string = is_numeric( $date ) ? "@{$date}" : $date;
		$utc_date        = new DateTime( $utc_date_string, new DateTimeZone( 'UTC' ) );
		$from            = Tribe__Timezones::to_tz( $utc_date->format( Tribe__Date_Utils::DBDATETIMEFORMAT ), Tribe__Timezones::wp_timezone_string() );

		return [
			'meta_query' => [
				'available-from' => [
					'not-exists' => [
						'key'     => '_ticket_start_date',
						'compare' => 'NOT EXISTS',
					],
					'relation'   => 'OR',
					'from'       => [
						'key'     => '_ticket_start_date',
						'compare' => '>=',
						'value'   => $from,
					],
				],
			],
		];
	}

	/**
	 * Filters tickets by their available date being until a date.
	 *
	 * @since 4.8
	 *
	 * @param string|int $date
	 *
	 * @return array
	 * @throws Exception
	 *
	 */
	public function filter_by_available_until( $date ) {
		// the input is a UTC date or timestamp
		$utc_date_string = is_numeric( $date ) ? "@{$date}" : $date;
		$utc_date        = new DateTime( $utc_date_string, new DateTimeZone( 'UTC' ) );
		$until           = Tribe__Timezones::to_tz( $utc_date->format( Tribe__Date_Utils::DBDATETIMEFORMAT ), Tribe__Timezones::wp_timezone_string() );

		return [
			'meta_query' => [
				'available-until' => [
					'not-exists' => [
						'key'     => '_ticket_end_date',
						'compare' => 'NOT EXISTS',
					],
					'relation'   => 'OR',
					'from'       => [
						'key'     => '_ticket_end_date',
						'compare' => '<=',
						'value'   => $until,
					],
				],
			],
		];
	}

	/**
	 * Filters tickets by if they are currently available or available in the future.
	 *
	 * @since 5.2.0
	 *
	 * @return array
	 * @throws Exception
	 *
	 */
	public function filter_by_active() {
		// the input is a UTC date or timestamp
		$utc_date = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
		$now      = Tribe__Timezones::to_tz( $utc_date->format( Tribe__Date_Utils::DBDATETIMEFORMAT ), Tribe__Timezones::wp_timezone_string() );

		return [
			'meta_query' => [
				'available-until' => [
					'not-exists' => [
						'key'     => '_ticket_end_date',
						'compare' => 'NOT EXISTS',
					],
					'relation'   => 'OR',
					'from'       => [
						'key'     => '_ticket_end_date',
						'compare' => '>',
						'value'   => $now,
					],
				],
			],
		];
	}

	/**
	 * Filters tickets to only get those related to posts with a specific status.
	 *
	 * @since 4.8
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
				$statuses = [ 'publish' ];
			} else {
				// no need to filter if the user can read all posts
				return;
			}
		}

		if ( ! $can_read_private_posts ) {
			$event_status = array_intersect( $statuses, [ 'publish' ] );
		}

		if ( empty( $event_status ) ) {
			throw Tribe__Repository__Void_Query_Exception::because_the_query_would_yield_no_results( 'The user cannot read posts with the requested post statuses.' );
		}

		$this->where_meta_related_by( $this->ticket_to_event_keys(), 'IN', 'post_status', $statuses );
	}

	/**
	 * Filters tickets depending on them having additional
	 * information available and active or not.
	 *
	 * @since 4.8
	 *
	 * @param bool $exists
	 *
	 * @return array
	 */
	public function filter_by_attendee_meta_existence( $exists ) {
		if ( ! class_exists( 'Tribe__Tickets_Plus__Meta' ) ) {
			return [];
		}

		if ( $exists ) {
			return [
				'meta_query' => [
					'by-attendee-meta-availability' => [
						'is-enabled' => [
							'key'     => Tribe__Tickets_Plus__Meta::ENABLE_META_KEY,
							'compare' => '=',
							'value'   => 'yes',
						],
						'relation'   => 'AND',
						'has-meta'   => [
							'key'     => Tribe__Tickets_Plus__Meta::META_KEY,
							'compare' => 'EXISTS',
						],
					],
				],
			];
		}

		return [
			'meta_query' => [
				'by-attendee-meta-availability' => [
					'is-not-enabled' => [
						'key'     => Tribe__Tickets_Plus__Meta::ENABLE_META_KEY,
						'compare' => '!=',
						'value'   => 'yes',
					],
					'relation'       => 'OR',
					'not-exists'     => [
						'key'     => Tribe__Tickets_Plus__Meta::ENABLE_META_KEY,
						'compare' => 'NOT EXISTS',
					],
				],
			],
		];
	}

	/**
	 * Filters tickets by their provider currency codes.
	 *
	 * Applying this filter automatically excludes RSVP tickets that, being free, have
	 * no currency and hence no code.
	 *
	 * @since 4.8
	 *
	 * @param string|array $currency_code A 3-letter currency code, an array of CSV list of
	 *                                    3-letter currency codes.
	 *
	 * @throws Tribe__Repository__Void_Query_Exception If the queried currency code would make it
	 *                                                 so that no ticket would match the query.
	 */
	public function filter_by_currency_code( $currency_code ) {
		$queried_codes = Tribe__Utils__Array::list_to_array( $currency_code );

		if ( empty( $queried_codes ) ) {
			return;
		}

		$queried_codes = array_map( 'strtoupper', $queried_codes );

		$keys             = $this->ticket_to_event_keys();
		$provider_symbols = $this->ticket_provider_symbols();

		$in_keys = [];

		foreach ( $provider_symbols as $provider_slug => $provider_code ) {
			$intersected = array_intersect( (array) $provider_code, $queried_codes );

			if ( count( $intersected ) === 0 ) {
				continue;
			}

			$in_keys[] = $keys[ $provider_slug ];
		}

		if ( empty( $in_keys ) ) {
			$reason = 'No active provider has one of the queried currency symbols';
			throw Tribe__Repository__Void_Query_Exception::because_the_query_would_yield_no_results( $reason );
		}

		$this->by( 'meta_exists', $in_keys );
	}

	/**
	 * Get list of provider symbols.
	 *
	 * @since 4.10.6
	 *
	 * @return array List of provider symbols.
	 */
	public function ticket_provider_symbols() {
		$provider_symbols = [];

		if ( tribe( 'tickets.commerce.paypal' )->is_active() ) {
			/** @var Tribe__Tickets__Commerce__Currency $currency */
			$currency = tribe( 'tickets.commerce.paypal.currency' );

			$provider_symbols['tribe-commerce'] = $currency->get_currency_code();
		}

		return $provider_symbols;
	}

	/**
	 * {@inheritdoc}
	 */
	public function create() {
		// Prevent creation of Tickets through the default ORM.
		if ( 1 !== count( $this->ticket_types() ) ) {
			return false;
		}

		return parent::create();
	}

	/**
	 * Internal method to filter Tickets by keeping only those either of a certain type, or not
	 * of a certain type.
	 *
	 * @since 5.8.2
	 *
	 * @param string          $operator Either `IN` or `NOT IN` to keep, respectively, Tickets of a specific type,
	 *                                  or Tickets that have not a specific type.
	 * @param string|string[] $type     The type of Tickets to keep or exclude.
	 *
	 * @return void WHERE and JOIN clauses are added to the query being built.
	 */
	private function filter_by_type_operator( string $operator, $type ): void {
		$hash  = substr( md5( microtime() ), - 5 );
		$types = (array) $type;
		global $wpdb;
		$types_set = $wpdb->prepare(
			implode( ",", array_fill( 0, count( $types ), '%s' ) ),
			...$types
		);

		// Include tickets that have their `_type` meta set to `default` or that have no `_type` meta.
		$alias = 'ticket_type_filter_' . $hash;
		$this->filter_query->join( "LEFT JOIN {$wpdb->postmeta} AS {$alias}
			 ON {$wpdb->posts}.ID = {$alias}.post_id
			 AND {$alias}.meta_key = '_type'" );
		$this->filter_query->where( "COALESCE({$alias}.meta_value, 'default') {$operator} (" . $types_set . ")" );
	}

	/**
	 * Filters the ticket to be returned by the value of the `_type` meta key.
	 *
	 * @since 5.8.2
	 *
	 * @param string|string[] $type The ticket type or types to filter by.
	 *
	 * @return void The query is modified in place.
	 */
	public function filter_by_type( $type ): void {
		$this->filter_by_type_operator( 'IN', $type );
	}

	/**
	 * Captures the SQL that would be used to get the Ticket IDs without runing the query.
	 *
	 * @since 5.8.0
	 *
	 * @return string|null The SQL query or `null` if the query cannot be run.
	 */
	private function get_ids_request(): ?string {
		$posts_request = null;
		$squeezer      = static function ( $pre, \WP_Query $query ) use ( &$posts_request ) {
			$posts_request = $query->request;

			// Avoid the query from actually running by returning a non null value.
			return [];
		};
		add_filter( 'posts_pre_query', $squeezer, PHP_INT_MAX, 2 );
		$this->set_found_rows( false )->per_page( - 1 )->get_ids();
		remove_filter( 'posts_pre_query', $squeezer, PHP_INT_MAX );

		return $posts_request;
	}

	/**
	 * Get the independent capacity of the Tickets queried by the repository.
	 *
	 * The independent capacity does not include the capacity of Tickets with Unlimited capacity.
	 *
	 * @since 5.8.0
	 *
	 * @return int The independent capacity of the Tickets queried by the repository.
	 */
	public function get_independent_capacity(): int {
		$posts_request = $this->get_ids_request();

		if ( empty( $posts_request ) ) {
			return 0;
		}

		/*
		 * Run the query using the sub-query, this will not pull out potentially unbound data (the Tickets) from
		 * the database but only the sum of their capacity (an aggregate function).
		 * The whole query, and likely most, if not all, the sub-query, will hit indexes.
		 */
		global $wpdb;
		/**
		 * @var Tickets_Handler $tickets_handler
		 */
		$tickets_handler   = tribe( 'tickets.handler' );
		$capacity_meta_key = $tickets_handler->key_capacity;
		$mode_meta_key     = Global_Stock::TICKET_STOCK_MODE;
		$query             = $wpdb->prepare(
			"SELECT SUM(capacity.meta_value) FROM {$wpdb->postmeta} capacity
				INNER JOIN {$wpdb->postmeta} stock_mode ON capacity.post_id = stock_mode.post_id
					 AND stock_mode.meta_key = %s
				WHERE capacity.meta_key = %s
				AND capacity.post_id IN ($posts_request)
				AND stock_mode.meta_value = %s
				AND capacity.meta_value >= 0
				",
			$mode_meta_key,
			$capacity_meta_key,
			Global_Stock::OWN_STOCK_MODE
		);

		return (int) DB::get_var( $query );
	}

	/**
	 * Get the shared capacity of the Tickets queried by the repository.
	 *
	 * The shared capacity does not include the capacity of Tickets with Unlimited capacity.
	 *
	 * @since 5.8.0
	 *
	 * @return int The shared capacity of the Tickets queried by the repository.
	 */
	public function get_shared_capacity(): int {
		$posts_request = $this->get_ids_request();

		if ( empty( $posts_request ) ) {
			return 0;
		}

		/*
		 * Run the query using the sub-query, this will not pull out potentially unbound data (the Tickets) from
		 * the database but only the max of their capacity (an aggregate function).
		 * The whole query, and likely most, if not all, the sub-query, will hit indexes.
		 */
		global $wpdb;
		/**
		 * @var Tickets_Handler $tickets_handler
		 */
		$tickets_handler   = tribe( 'tickets.handler' );
		$capacity_meta_key = $tickets_handler->key_capacity;
		$mode_meta_key     = Global_Stock::TICKET_STOCK_MODE;
		$query             = $wpdb->prepare(
			"SELECT MAX(CAST(capacity.meta_value AS UNSIGNED)) FROM {$wpdb->postmeta} capacity
				INNER JOIN {$wpdb->postmeta} stock_mode ON capacity.post_id = stock_mode.post_id
					 AND stock_mode.meta_key = %s
				WHERE capacity.meta_key = %s
				AND capacity.post_id IN ($posts_request)
				AND stock_mode.meta_value in (%s, %s)
				AND capacity.meta_value >= 0
				",
			$mode_meta_key,
			$capacity_meta_key,
			Global_Stock::GLOBAL_STOCK_MODE,
			Global_Stock::CAPPED_STOCK_MODE
		);

		return (int) DB::get_var( $query );
	}

	/**
	 * Filters tickets by their global stock mode.
	 *
	 * @since 5.8.0
	 *
	 * @param array<string>|string $modes             The global stock mode or modes to filter by, use the
	 *                                                `Global_Stock::` constants.
	 * @param bool                 $exclude_unlimited Whether to exclude tickets with Unlimited capacity or not,
	 *                                                defaults to `false`.
	 */
	public function filter_by_global_stock_mode( $modes, bool $exclude_unlimited = false ): void {
		if ( (array) $modes === [ Global_Stock::UNLIMITED_STOCK_MODE ] ) {
			$this->where( 'meta_equals', Tickets_Handler::instance()->key_capacity, '-1' );

			return;
		}

		$this->where( 'meta_in', Global_Stock::TICKET_STOCK_MODE, (array) $modes );

		if ( $exclude_unlimited ) {
			$capacity_meta_key = Tickets_Handler::instance()->key_capacity;
			$this->where( 'meta_gte', $capacity_meta_key, 0 );
		}
	}

	/**
	 * Filters the ticket to be excluded by the value of the `_type` meta key.
	 *
	 * @since 5.8.2
	 *
	 * @param string|string[] $type The ticket type or types to exclude from the results.
	 *
	 * @return void The query is modified in place.
	 */
	public function filter_by_type_not_in( $type ): void {
		$this->filter_by_type_operator( 'NOT IN', $type );
	}
}
