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
	 * @param int    $id      The post ID.
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
		$attendee    = get_post( $attendee_id );

		if ( empty( $attendee ) || $attendee->post_type !== Tribe__Tickets__RSVP::ATTENDEE_OBJECT ) {
			return new WP_Error( 'ticket-not-found', $this->messages->get_message( 'ticket-not-found' ) );
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
		// TODO: Implement get_ticket_data() method.
	}
}