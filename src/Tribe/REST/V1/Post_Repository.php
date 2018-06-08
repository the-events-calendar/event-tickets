<?php


class Tribe__Tickets__REST__V1__Post_Repository implements Tribe__Tickets__REST__Interfaces__Post_Repository {

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
		/** @var Tribe__Tickets__Tickets $provider */
		$provider = tribe_tickets_get_ticket_provider( $ticket_id );

		if ( ! $provider instanceof Tribe__Tickets__Tickets ) {
			return new WP_Error( 'ticket-provider-not-found', $this->messages->get_message( 'ticket-provider-not-found' ) );
		}


		$post = $provider->get_event_for_ticket( $ticket_id );

		if ( ! $post instanceof WP_Post ) {
			return new WP_Error( 'ticket-post-not-found', $this->messages->get_message( 'ticket-post-not-found' ) );
		}

		// make sure the data is a nested array
		$data = json_decode( json_encode( $provider->get_ticket( $post->ID, $ticket_id ) ), true );

		$this->reformat_data( $data );

		return $data;
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
}
