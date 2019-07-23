<?php

/**
 * Class Tribe__Tickets__Attendee_Repository
 *
 * The basic Attendee repository.
 *
 * @since 4.8
 */
class Tribe__Tickets__Attendee_Repository extends Tribe__Repository {

	/**
	 * The unique fragment that will be used to identify this repository filters.
	 *
	 * @var string
	 */
	protected $filter_name = 'attendees';

	/**
	 * @var array An array of all the order statuses supported by the repository.
	 */
	protected static $order_statuses;

	/**
	 * @var array An array of all the public order statuses supported by the repository.
	 *            This list is hand compiled as reduced and easier to maintain.
	 */
	protected static $public_order_statuses = [
		'yes',     // RSVP
		'completed', // PayPal
		'wc-completed', // WooCommerce
		'publish', // Easy Digital Downloads
	];

	/**
	 * @var array An array of all the private order statuses supported by the repository.
	 */
	protected static $private_order_statuses;

	/**
	 * Tribe__Tickets__Attendee_Repository constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->create_args['post_type'] = current( $this->attendee_types() );

		$this->default_args = array_merge( $this->default_args, [
			'post_type'   => $this->attendee_types(),
			'orderby'     => [ 'date', 'title', 'ID' ],
			'post_status' => 'any',
		] );

		// Add initial simple schema.
		$this->add_simple_meta_schema_entry( 'event', $this->attendee_to_event_keys(), 'meta_in' );
		$this->add_simple_meta_schema_entry( 'event__not_in', $this->attendee_to_event_keys(), 'meta_not_in' );
		$this->add_simple_meta_schema_entry( 'ticket', $this->attendee_to_ticket_keys(), 'meta_in' );
		$this->add_simple_meta_schema_entry( 'ticket__not_in', $this->attendee_to_ticket_keys(), 'meta_not_in' );
		$this->add_simple_meta_schema_entry( 'order', $this->attendee_to_order_keys(), 'meta_in' );
		$this->add_simple_meta_schema_entry( 'order__not_in', $this->attendee_to_order_keys(), 'meta_not_in' );
		$this->add_simple_meta_schema_entry( 'product_id', $this->attendee_to_ticket_keys(), 'meta_in' );
		$this->add_simple_meta_schema_entry( 'product_id__not_in', $this->attendee_to_ticket_keys(), 'meta_not_in' );
		$this->add_simple_meta_schema_entry( 'purchaser_name', $this->purchaser_name_keys(), 'meta_in' );
		$this->add_simple_meta_schema_entry( 'purchaser_name__not_in', $this->purchaser_name_keys(), 'meta_not_in' );
		$this->add_simple_meta_schema_entry( 'purchaser_name__like', $this->purchaser_name_keys(), 'meta_like' );
		$this->add_simple_meta_schema_entry( 'purchaser_email', $this->purchaser_email_keys(), 'meta_in' );
		$this->add_simple_meta_schema_entry( 'purchaser_email__not_in', $this->purchaser_email_keys(), 'meta_not_in' );
		$this->add_simple_meta_schema_entry( 'purchaser_email__like', $this->purchaser_email_keys(), 'meta_like' );
		$this->add_simple_meta_schema_entry( 'security_code', $this->security_code_keys(), 'meta_in' );
		$this->add_simple_meta_schema_entry( 'security_code__not_in', $this->security_code_keys(), 'meta_not_in' );
		$this->add_simple_meta_schema_entry( 'user', '_tribe_tickets_attendee_user_id', 'meta_in' );
		$this->add_simple_meta_schema_entry( 'user__not_in', '_tribe_tickets_attendee_user_id', 'meta_not_in' );
		$this->add_simple_meta_schema_entry( 'price', '_paid_price' );

		$this->schema = array_merge( $this->schema, [
			'optout'               => [ $this, 'filter_by_optout' ],
			'rsvp_status'          => [ $this, 'filter_by_rsvp_status' ],
			'rsvp_status__or_none' => [ $this, 'filter_by_rsvp_status_or_none' ],
			'provider'             => [ $this, 'filter_by_provider' ],
			'provider__not_in'     => [ $this, 'filter_by_provider_not_in' ],
			'event_status'         => [ $this, 'filter_by_event_status' ],
			'order_status'         => [ $this, 'filter_by_order_status' ],
			'order_status__not_in' => [ $this, 'filter_by_order_status_not_in' ],
			'price_min'            => [ $this, 'filter_by_price_min' ],
			'price_max'            => [ $this, 'filter_by_price_max' ],
			'has_attendee_meta'    => [ $this, 'filter_by_attendee_meta_existence' ],
			'checkedin'            => [ $this, 'filter_by_checkedin' ],
		] );

		$this->init_order_statuses();
	}

	/**
	 * Returns an array of the attendee types handled by this repository.
	 *
	 * Extending repository classes should override this to add more attendee types.
	 *
	 * @since 4.8
	 *
	 * @return array
	 */
	public function attendee_types() {
		return [
			'rsvp'           => 'tribe_rsvp_attendees',
			'tribe-commerce' => 'tribe_tpp_attendees',
		];
	}

	/**
	 * Returns the list of meta keys relating an Attendee to a Post (Event).
	 *
	 * Extending repository classes should override this to add more keys.
	 *
	 * @since 4.8
	 *
	 * @return array
	 */
	public function attendee_to_event_keys() {
		return [
			'rsvp'           => '_tribe_rsvp_event',
			'tribe-commerce' => '_tribe_tpp_event',
		];
	}

	/**
	 * Returns the list of meta keys relating an Attendee to a Ticket.
	 *
	 * Extending repository classes should override this to add more keys.
	 *
	 * @since 4.8
	 *
	 * @return array
	 */
	public function attendee_to_ticket_keys() {
		return [
			'rsvp'           => '_tribe_rsvp_product',
			'tribe-commerce' => '_tribe_tpp_product',
		];
	}

	/**
	 * Returns a list of meta keys relating an attendee to the order
	 * that generated it.
	 *
	 * @since 4.8
	 *
	 * @return array
	 */
	protected function attendee_to_order_keys() {
		return [
			'rsvp'           => '_tribe_rsvp_order',
			'tribe-commerce' => '_tribe_tpp_order',
		];
	}

	/**
	 * Returns the list of meta keys relating an Attendee to a Post (Event).
	 *
	 * Extending repository classes should override this to add more keys.
	 *
	 * @since 4.10.6
	 *
	 * @return array
	 */
	public function purchaser_name_keys() {
		return [
			'rsvp'           => '_tribe_rsvp_full_name',
			'tribe-commerce' => '_tribe_tpp_full_name',
		];
	}

	/**
	 * Returns the list of meta keys relating an Attendee to a Post (Event).
	 *
	 * Extending repository classes should override this to add more keys.
	 *
	 * @since 4.10.6
	 *
	 * @return array
	 */
	public function purchaser_email_keys() {
		return [
			'rsvp'           => '_tribe_rsvp_email',
			'tribe-commerce' => '_tribe_tpp_email',
		];
	}

	/**
	 * Returns the list of meta keys relating an Attendee to a Post (Event).
	 *
	 * Extending repository classes should override this to add more keys.
	 *
	 * @since 4.10.6
	 *
	 * @return array
	 */
	public function security_code_keys() {
		return [
			'rsvp'           => '_tribe_rsvp_security_code',
			'tribe-commerce' => '_tribe_tpp_security_code',
		];
	}

	/**
	 * Returns the list of meta keys denoting an Attendee optout choice.
	 *
	 * Extending repository classes should override this to add more keys.
	 *
	 * @since 4.8
	 *
	 * @return array
	 */
	public function attendee_optout_keys() {
		return [
			'rsvp'           => '_tribe_rsvp_attendee_optout',
			'tribe-commerce' => '_tribe_tpp_attendee_optout',
		];
	}

	/**
	 * Returns a list of meta keys indicating an attendee checkin status.
	 *
	 * @since 4.8
	 *
	 * @return array
	 */
	public function checked_in_keys() {
		return [
			'rsvp'           => '_tribe_rsvp_checkedin',
			'tribe-commerce' => '_tribe_tpp_checkedin',
		];
	}

	/**
	 * Provides arguments to filter attendees by their optout status.
	 *
	 * @since 4.8
	 *
	 * @param string $optout An optout option, supported 'yes','no','any'.
	 *
	 * @return array|null
	 */
	public function filter_by_optout( $optout ) {
		global $wpdb;

		switch ( $optout ) {
			case 'any':
				return null;
				break;
			case 'no':
				$this->by( 'meta_not_in', $this->attendee_optout_keys(), [ 'yes', 1 ] );
				break;
			case 'yes':
				$this->by( 'meta_in', $this->attendee_optout_keys(), [ 'yes', 1 ] );
				break;
			case 'no_or_none':
				$optout_keys = $this->attendee_optout_keys();
				$optout_keys = array_map( [ $wpdb, '_real_escape' ], $optout_keys );
				$optout_keys = '"' . implode( '", "', $optout_keys ) . '"';

				$this->filter_query->join( "
					LEFT JOIN {$wpdb->postmeta} attendee_optout
					ON ( attendee_optout.post_id = {$wpdb->posts}.ID
						AND attendee_optout.meta_key IN ( {$optout_keys} ) )
				" );

				$this->filter_query->where( "(
					attendee_optout.post_id IS NULL
					OR attendee_optout.meta_value NOT IN ( 'yes', '1' )
				)" );

				break;
		}

		return null;
	}

	/**
	 * Provides arguments to filter attendees by a specific RSVP status.
	 *
	 * @since 4.8
	 *
	 * @param string $rsvp_status
	 *
	 * @return array
	 */
	public function filter_by_rsvp_status( $rsvp_status ) {
		return Tribe__Repository__Query_Filters::meta_in(
			Tribe__Tickets__RSVP::ATTENDEE_RSVP_KEY,
			$rsvp_status,
			'by-rsvp-status'
		);
	}

	/**
	 * Provides arguments to filter attendees by a specific RSVP status or no status at all.
	 *
	 * Mind that we allow tickets not to have an RSVP status at all and
	 * still match. This assumes that all RSVP tickets will have a status
	 * assigned (which is the default behaviour).
	 *
	 * @since 4.8
	 *
	 * @param string $rsvp_status
	 *
	 * @return array
	 */
	public function filter_by_rsvp_status_or_none( $rsvp_status ) {
		return Tribe__Repository__Query_Filters::meta_in_or_not_exists(
			Tribe__Tickets__RSVP::ATTENDEE_RSVP_KEY,
			$rsvp_status,
			'by-rsvp-status-or-none'
		);
	}

	/**
	 * Provides arguments to filter attendees by the ticket provider.
	 *
	 * To avoid lengthy queries we check if a provider specific meta
	 * key relating the Attendee to the event (a post) is set.
	 *
	 * @since 4.8
	 *
	 * @param string|array $provider A provider supported slug or an
	 *                               array of supported provider slugs.
	 *
	 * @return array
	 */
	public function filter_by_provider( $provider ) {
		$providers = Tribe__Utils__Array::list_to_array( $provider );
		$meta_keys = Tribe__Utils__Array::map_or_discard( (array) $providers, $this->attendee_to_event_keys() );

		$this->by( 'meta_exists', $meta_keys );
	}

	/**
	 * Provides arguments to exclude attendees by the ticket provider.
	 *
	 * To avoid lengthy queries we check if a provider specific meta
	 * key relating the Attendee to the event (a post) is not set.
	 *
	 * @since 4.8
	 *
	 * @param string|array $provider A provider supported slug or an
	 *                               array of supported provider slugs.
	 *
	 * @return array
	 */
	public function filter_by_provider_not_in( $provider ) {
		$providers = Tribe__Utils__Array::list_to_array( $provider );
		$meta_keys = Tribe__Utils__Array::map_or_discard( (array) $providers, $this->attendee_to_event_keys() );

		$this->by( 'meta_not_exists', $meta_keys );
	}

	/**
	 * Filters attendee to only get those related to posts with a specific status.
	 *
	 * @since 4.8
	 *
	 * @param string|array $event_status
	 *
	 * @throws Tribe__Repository__Void_Query_Exception If the requested statuses are not accessible by the user.
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
			throw Tribe__Repository__Void_Query_Exception::because_the_query_would_yield_no_results(
				'The user cannot read posts with the requested post statuses.'
			);
		}

		$this->where_meta_related_by(
			$this->attendee_to_event_keys(),
			'IN',
			'post_status',
			$statuses
		);
	}

	/**
	 * Filters attendee to only get those related to orders with a specific ID.
	 *
	 * @since TVD
	 *
	 * @param string|array $order_id Order ID(s).
	 */
	public function filter_by_order( $order_id ) {
		$order_ids = Tribe__Utils__Array::list_to_array( $order_id );

		$this->by( 'meta_in', $this->attendee_to_order_keys(), $order_ids );
	}

	/**
	 * Filters attendee to only get those related to orders with a specific status.
	 *
	 * @since 4.8
	 *
	 * @param string|array $order_status Order status.
	 * @param string       $type         Type of matching (in, not_in, like).
	 *
	 * @throws Tribe__Repository__Void_Query_Exception If the requested statuses are not accessible by the user.
	 */
	public function filter_by_order_status( $order_status, $type = 'in' ) {
		$statuses = Tribe__Utils__Array::list_to_array( $order_status );

		$can_read_private_posts = current_user_can( 'read_private_posts' );

		// map the `any` meta-status
		if ( 1 === count( $statuses ) && 'any' === $statuses[0] ) {
			if ( ! $can_read_private_posts ) {
				$statuses = [ 'public' ];
			} else {
				// no need to filter if the user can read all posts
				return;
			}
		}

		// Allow the user to define singular statuses or the meta-status "public"
		if ( in_array( 'public', $statuses, true ) ) {
			$statuses = array_unique( array_merge( $statuses, self::$public_order_statuses ) );
		}

		// Allow the user to define singular statuses or the meta-status "private"
		if ( in_array( 'private', $statuses, true ) ) {
			$statuses = array_unique( array_merge( $statuses, self::$private_order_statuses ) );
		}

		// Remove any status the user cannot access
		if ( ! $can_read_private_posts ) {
			$statuses = array_intersect( $statuses, self::$public_order_statuses );
		}

		if ( empty( $statuses ) ) {
			throw Tribe__Repository__Void_Query_Exception::because_the_query_would_yield_no_results(
				'The user cannot access the requested attendee order statuses.'
			);
		}

		/** @var wpdb $wpdb */
		global $wpdb;

		$value_operator = 'IN';
		$value_clause   = "( '" . implode( "','", array_map( [ $wpdb, '_escape' ], $statuses ) ) . "' )";

		if ( 'not_in' === $type ) {
			$value_operator = 'NOT IN';
		}

		$has_plus_providers = class_exists( 'Tribe__Tickets_Plus__Commerce__EDD__Main' )
		                      || class_exists( 'Tribe__Tickets_Plus__Commerce__WooCommerce__Main' );

		$this->filter_query->join( "
			LEFT JOIN {$wpdb->postmeta} order_status_meta
			ON order_status_meta.post_id = {$wpdb->posts}.ID
		", 'order-status-meta' );

		$et_where_clause = "
			(
				order_status_meta.meta_key IN ( '_tribe_rsvp_status', '_tribe_tpp_status' )
				AND order_status_meta.meta_value {$value_operator} {$value_clause}
			)
		";

		if ( ! $has_plus_providers ) {
			$this->filter_query->where( $et_where_clause );
		} else {
			$this->filter_query->join( "
				LEFT JOIN {$wpdb->posts} order_status_post
				ON order_status_post.ID = order_status_meta.meta_value
			", 'order-status-post' );

			$this->filter_query->where( "
				(
					{$et_where_clause}
					OR (
						order_status_meta.meta_key IN ( '_tribe_wooticket_order','_tribe_eddticket_order' )
						AND order_status_post.post_status {$value_operator} {$value_clause}
					)
				)
			" );
		}
	}

	/**
	 * Filters attendee to only get those not related to orders with a specific status.
	 *
	 * @since 4.10.6
	 *
	 * @param string|array $order_status
	 *
	 * @throws Tribe__Repository__Void_Query_Exception If the requested statuses are not accessible by the user.
	 */
	public function filter_by_order_status_not_in( $order_status ) {
		$this->filter_by_order_status( $order_status, 'not_in' );
	}

	/**
	 * Filters Attendees by a minimum paid price.
	 *
	 * @since 4.8
	 *
	 * @param int $price_min
	 */
	public function filter_by_price_min( $price_min ) {
		$this->by( 'meta_gte', '_paid_price', (int) $price_min );
	}

	/**
	 * Filters Attendees by a maximum paid price.
	 *
	 * @since 4.8
	 *
	 * @param int $price_max
	 */
	public function filter_by_price_max( $price_max ) {
		$this->by( 'meta_lte', '_paid_price', (int) $price_max );
	}

	/**
	 * Filters attendee depending on them having additional
	 * information or not.
	 *
	 * @since 4.8
	 *
	 * @param bool $exists
	 */
	public function filter_by_attendee_meta_existence( $exists ) {
		if ( $exists ) {
			$this->by( 'meta_exists', '_tribe_tickets_meta' );
		} else {
			$this->by( 'meta_not_exists', '_tribe_tickets_meta' );
		}
	}

	/**
	 * Filters attendees depending on their checkedin status.
	 *
	 * @since 4.8
	 *
	 * @param bool $checkedin
	 *
	 * @return array
	 */
	public function filter_by_checkedin( $checkedin ) {
		$meta_keys = $this->checked_in_keys();

		if ( tribe_is_truthy( $checkedin ) ) {
			return Tribe__Repository__Query_Filters::meta_in( $meta_keys, '1', 'is-checked-in' );
		}

		return Tribe__Repository__Query_Filters::meta_not_in_or_not_exists( $meta_keys, '1', 'is-not-checked-in' );
	}

	/**
	 * Bootstrap method called once per request to compile the available
	 * order statuses.
	 *
	 * @since 4.8
	 *
	 * @return bool|string
	 */
	protected function init_order_statuses() {
		if ( empty( self::$order_statuses ) ) {
			// For RSVP tickets the order status is the going status
			$statuses = [ 'yes', 'no' ];

			if ( Tribe__Tickets__Commerce__PayPal__Main::get_instance()->is_active() ) {
				$statuses = array_merge( $statuses, tribe( 'tickets.status' )->get_statuses_by_action( 'all', 'tpp' ) );
			}

			if (
				class_exists( 'Tribe__Tickets_Plus__Commerce__WooCommerce__Main' )
				&& function_exists( 'wc_get_order_statuses' )
			) {
				$statuses = array_merge( $statuses, tribe( 'tickets.status' )->get_statuses_by_action( 'all', 'woo' ) );
			}

			if (
				class_exists( 'Tribe__Tickets_Plus__Commerce__EDD__Main' )
				&& function_exists( 'edd_get_payment_statuses' )
			) {
				$statuses = array_merge( $statuses, array_keys( tribe( 'tickets.status' )->get_statuses_by_action( 'all', 'edd' ) ) );
			}

			self::$order_statuses         = $statuses;
			self::$private_order_statuses = array_diff( $statuses, self::$public_order_statuses );
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function create() {
		// Disabled for now.
		return false;
	}

	/**
	 * Get key from list of keys if it exists and fallback to empty array.
	 *
	 * @since 4.10.5
	 *
	 * @param string $key  Key name.
	 * @param array  $list List of keys.
	 *
	 * @return array List of matching keys.
	 */
	protected function limit_list( $key, $list ) {
		if ( ! array_key_exists( $key, $list ) ) {
			return [];
		}

		return [
			$key => $list[ $key ],
		];
	}
}
