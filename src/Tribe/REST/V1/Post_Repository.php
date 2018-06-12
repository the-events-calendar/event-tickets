<?php


class Tribe__Tickets__REST__V1__Post_Repository
	extends Tribe__REST__Post_Repository
	implements Tribe__Tickets__REST__Interfaces__Post_Repository {

	/**
	 * A post type to get data request handler map.
	 *
	 * @var array
	 */
	protected $types_get_map = array();

	/**
	 * @var Tribe__REST__Messages_Interface
	 */
	protected $messages;

	private $global_id_lineage_key = '_tribe_global_id_lineage';

	public function __construct( Tribe__REST__Messages_Interface $messages = null ) {
		$this->types_get_map = array(
			Tribe__Tickets__RSVP::ATTENDEE_OBJECT => array( $this, 'get_attendee_data' ),
		);

		$this->messages = $messages ? $messages : tribe( 'tickets.rest-v1.messages' );
	}

	/**
	 * Retrieves an array representation of the post.
	 *
	 * @since TBD
	 *
	 * @param int $id The post ID.
	 * @param string $context Context of data.
	 *
	 * @return array An array representation of the post.
	 */
	public function get_data( $id, $context = '' ) {
		$post = get_post( $id );

		if ( empty( $post ) ) {
			return array();
		}

		if ( ! isset( $this->types_get_map[ $post->post_type ] ) ) {
			return (array) $post;
		}

		return call_user_func( $this->types_get_map[ $post->post_type ], $id, $context );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_attendee_data( $attendee_id, $context = '' ) {
		$attendee = get_post( $attendee_id );

		if ( empty( $attendee ) || $attendee->post_type !== Tribe__Tickets__RSVP::ATTENDEE_OBJECT ) {
			return new WP_Error( 'attendee-not-found', $this->messages->get_message( 'attendee-not-found' ) );
		}

		$attendee_id = $attendee->ID;

		$data = array(
			'id'     => $attendee_id,
			'status' => $attendee->post_status,
		);

		/**
		 * Filters the data that will be returned if for a single attendee.
		 *
		 * @since  TBD
		 *
		 * @param array $data The data that will be returned in the response.
		 * @param WP_Post $attendee The requested attendee post object.
		 */
		return apply_filters( 'tribe_tickets_rest_attendee_data', $data, $attendee );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_ticket_data( $ticket_id, $context = '' ) {
		$ticket = $this->get_ticket_object($ticket_id);

		if ( $ticket instanceof WP_Error ) {
			return $ticket;
		}

		// make sure the data is a nested array
		$data = json_decode( json_encode( $ticket ), true );

		$data['post_id']  = $post->ID;
		$data['provider'] = $this->get_provider_slug( $provider );

		try {
			$this->reformat_data( $data );
			$this->add_global_id_information( $data );
			$this->add_post_information( $data );
			$this->add_rest_data( $data );
		} catch ( Exception $e ) {
			if ( $e instanceof Tribe__REST__Exceptions__Exception ) {
				return new WP_Error( $e->getCode(), $e->getMessage() );
			}

			/** @var Tribe__REST__Exceptions__Exception $e */
			return new WP_Error(
				'error',
				__( 'An error happened while building the response: ', 'event-tickets' ) . $e->getMessage(),
				array( 'status' => $e->getStatus() )
			);
		}

		return $data;
	}

	/**
	 * Returns the slug for provider.
	 *
	 * @since TBD
	 *
	 * @param string|object $provider_class The provider object or class.
	 *
	 * @return string
	 */
	protected function get_provider_slug( $provider_class ) {
		if ( is_object( $provider_class ) ) {
			$provider_class = get_class( $provider_class );
		}

		$map = array(
			'Tribe__Tickets__RSVP' => 'rsvp',
		);

		/**
		 * Filters the provider class to slug map.
		 *
		 * @since TBD
		 *
		 * @param array $map A map in the shape [ <class> => <slug> ]
		 * @param string The provider class
		 */
		$map = apply_filters( 'tribe_tickets_rest_provider_slug_map', $map, $provider_class );

		$default = array_values( $map )[0];

		return Tribe__Utils__Array::get( $map, $provider_class, $default );
	}

	/**
	 * Reformats the data to stick with the expected format.
	 *
	 * @since TBD
	 *
	 * @param array $data
	 */
	protected function reformat_data( array &$data ) {
		$map = array(
			'ID' => 'id',
		);

		foreach ( $map as $from_key => $to_key ) {
			if ( isset( $data[ $from_key ] ) && ! isset( $data[ $to_key ] ) ) {
				$data[ $to_key ] = $data[ $from_key ];
				unset( $data[ $from_key ] );
			}
		}
	}

	/**
	 * Adds the global ID information to the ticket data.
	 *
	 * @since TBD
	 *
	 * @param array $data
	 *
	 * @throws Tribe__REST__Exceptions__Exception If the global ID generation fails.
	 */
	protected function add_global_id_information( array &$data ) {
		$provider_class = $data['provider_class'];
		$ticket_id      = $data['id'];

		$global_id = $this->get_ticket_global_id( $ticket_id, $provider_class );

		if ( false === $global_id ) {
			throw new Tribe__REST__Exceptions__Exception(
				$this->messages->get_message( 'error-global-id-generation' ),
				'error-global-id-generation',
				500
			);
		}

		$data['global_id']         = $global_id;
		$data['global_id_lineage'] = $this->get_ticket_global_id_lineage( $ticket_id, $global_id );
	}

	/**
	 * Returns a ticket global ID.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id
	 * @param string $provider_class
	 *
	 * @return bool|string
	 */
	public function get_ticket_global_id( $ticket_id, $provider_class = null ) {
		if ( null === $provider_class ) {
			/** @var Tribe__Tickets__Tickets $provider */
			$provider = tribe_tickets_get_ticket_provider( $ticket_id );

			if ( ! $provider instanceof Tribe__Tickets__Tickets ) {
				return false;
			}

			$provider_class = get_class( $provider );
		}

		$generator = new Tribe__Tickets__Global_ID();
		$generator->origin( home_url() );
		$type = $this->get_provider_slug( $provider_class );
		$generator->type( $type );

		$global_id = $generator->generate( array(
			'type' => $type,
			'id'   => $ticket_id,
		) );

		return $global_id;
	}

	/**
	 * Returns a ticket Global ID lineage.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id
	 * @param string $global_id
	 *
	 * @return array|bool
	 */
	public function get_ticket_global_id_lineage( $ticket_id, $global_id = null ) {
		if ( null === $global_id ) {
			$global_id = $this->get_ticket_global_id( $ticket_id );

			if ( false === $global_id ) {
				return false;
			}
		}

		$ticket_global_id_lineage = get_post_meta( $ticket_id, $this->global_id_lineage_key, true );

		return ! empty( $ticket_global_id_lineage )
			? array_unique( array_merge( (array) $ticket_global_id_lineage, array( $global_id ) ) )
			: array( $global_id );
	}

	/**
	 * Adds the ticket post information to the data.
	 *
	 * @since TBD
	 *
	 * @param array $data
	 *
	 * @throws Tribe__REST__Exceptions__Exception If the post fetch or parsing fails.
	 */
	protected function add_post_information( &$data ) {
		$ticket_post = get_post( $data['id'] );

		if ( ! $ticket_post instanceof WP_Post ) {
			throw new Tribe__REST__Exceptions__Exception(
				$this->messages->get_message( 'error-ticket-post' ),
				'error-ticket-post',
				500
			);
		}

		/** @var Tribe__Tickets__Tickets_Handler $handler */
		$handler = tribe( 'tickets.handler' );

		$data['author']                  = $ticket_post->post_author;
		$data['status']                  = $ticket_post->post_status;
		$data['date']                    = $ticket_post->post_date;
		$data['date_utc']                = $ticket_post->post_date_gmt;
		$data['modified']                = $ticket_post->post_modified;
		$data['modified_utc']            = $ticket_post->post_modified_gmt;
		$data['title']                   = $ticket_post->post_title;
		$data['description']             = $ticket_post->post_content;
		$data['image']                   = $this->get_ticket_header_image( $data['id'] );
		$data['available_from']          = $this->get_ticket_start_date( $data['id'] );
		$data['available_from_details']  = $this->get_ticket_start_date( $data['id'], true );
		$data['available_until']         = $this->get_ticket_end_date( $data['id'] );
		$data['available_until_details'] = $this->get_ticket_end_date( $data['id'], true );
		$data['capacity']                = $this->get_ticket_capacity( $data['id'] );
		$data['capacity_details']        = $this->get_ticket_capacity( $data['id'], true );
		$data['is_available']            = $data['capacity_details']['available_percentage'] > 0;

	}

	/**
	 * Returns a ticket header image information if set.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id
	 *
	 * @return bool|array
	 */
	public function get_ticket_header_image( $ticket_id ) {
		$post = tribe_events_get_ticket_event( $ticket_id );

		if ( empty( $post ) ) {
			return false;
		}

		/** @var Tribe__Tickets__Tickets_Handler $handler */
		$handler  = tribe( 'tickets.handler' );
		$image_id = (int) get_post_meta( $post->ID, $handler->key_image_header, true );

		if ( empty( $image_id ) ) {
			return false;
		}

		$data = $this->get_image_data( $image_id );

		/**
		 * Filters the data that will returned for a ticket header image if set.
		 *
		 * @param array $data The ticket header image array representation.
		 * @param WP_Post $ticket_id The requested ticket.
		 * @param WP_Post $post The post this ticket is related to.
		 */
		return apply_filters( 'tribe_rest_event_featured_image', $data, $ticket_id, $post );
	}

	/**
	 * Returns a ticket start date.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id
	 * @param bool $get_details Whether to get the date in string format (`false`) or the full details (`true`).
	 *
	 * @return string|array
	 */
	public function get_ticket_start_date( $ticket_id, $get_details = false ) {
		/** @var Tribe__Tickets__Tickets_Handler $handler */
		$handler = tribe( 'tickets.handler' );

		$start_date = get_post_meta( $ticket_id, $handler->key_start_date, true );

		return $get_details
			? $this->get_date_details( $start_date )
			: $start_date;
	}

	/**
	 * Returns a ticket end date.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id
	 * @param bool $get_details Whether to get the date in string format (`false`) or the full details (`true`).
	 *
	 * @return string|array
	 */
	public function get_ticket_end_date( $ticket_id, $get_details = false ) {
		/** @var Tribe__Tickets__Tickets_Handler $handler */
		$handler = tribe( 'tickets.handler' );

		$end_date = get_post_meta( $ticket_id, $handler->key_end_date, true );

		return $get_details
			? $this->get_date_details( $end_date )
			: $end_date;
	}

	/**
	 * Returns a ticket capacity or capacity details.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id
	 * @param bool $get_details
	 *
	 * @return array|bool|int The ticket capacity, the details if `$get_details` is set to `true`
	 *                        or `false` on failure.
	 */
	public function get_ticket_capacity( $ticket_id, $get_details = false ) {
		$ticket = $this->get_ticket_object( $ticket_id );

		if ( $ticket instanceof WP_Error ) {
			return false;
		}

		$capacity = $ticket->capacity();

		if ( ! $get_details ) {
			return $capacity;
		}

		$available = $ticket->available();

		$unlimited = - 1 === $available;
		if ( $unlimited ) {
			$available_percentage = 100;
		} else {
			$available_percentage = $capacity <= 0 || $available == 0 ? 0 : floor( $available / $capacity * 100 );
		}

		// @todo here we need to uniform the return values to indicated unlimited and oversold!

		return array(
			'available_percentage' => $available_percentage,
			'max'                  => $ticket->capacity(),
			'available'            => $ticket->available(),
			'sold'                 => $ticket->qty_sold(),
			'pending'              => $ticket->qty_pending(),
		);
	}

	/**
	 * Gets the ticket object from a ticket ID.
	 *
	 * @since TBD
	 *
	 * @param int|WP_Post $ticket_id
	 *
	 * @return Tribe__Tickets__Ticket_Object|bool The ticket object or `false`
	 */
	protected function get_ticket_object( $ticket_id ) {
		if ( $ticket_id instanceof WP_Post ) {
			$ticket_id = $ticket_id->ID;
		}

		/** @var Tribe__Tickets__Tickets $provider */
		$provider = tribe_tickets_get_ticket_provider( $ticket_id );

		if ( ! $provider instanceof Tribe__Tickets__Tickets ) {
			return new WP_Error( 'ticket-provider-not-found', $this->messages->get_message( 'ticket-provider-not-found' ) );
		}

		$post = $provider->get_event_for_ticket( $ticket_id );

		if ( ! $post instanceof WP_Post ) {
			return new WP_Error( 'ticket-post-not-found', $this->messages->get_message( 'ticket-post-not-found' ) );
		}

		/** @var Tribe__Tickets__Ticket_Object $ticket */
		$ticket = $provider->get_ticket( $post->ID, $ticket_id );

		if ( ! $ticket instanceof Tribe__Tickets__Ticket_Object ) {
			return new WP_Error('ticket-object-not-found', $this->messages->get_message('ticket-object-not-found'));
		}

		return $ticket;
	}

	/**
	 * Adds REST API related information to the returned data.
	 *
	 * @since TBD
	 *
	 * @param array $data
	 */
	protected function add_rest_data( &$data ) {
		/** @var Tribe__Tickets__REST__V1__Main $main */
		$main = tribe( 'tickets.rest-v1.main' );

		$data['rest_url'] = $main->get_url( '/tickets/' . $data['id'] );
	}

	public function get_ticket_cost( $ticket_id ) {
		$ticket =  $this->get_ticket_object( $ticket_id );

		if ( $ticket instanceof WP_Error ) {
			return false;
		}

		return tribe_get_cost()
	}
}
