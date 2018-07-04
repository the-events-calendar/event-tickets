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
			Tribe__Tickets__RSVP::ATTENDEE_OBJECT => array( $this, 'get_ticket_data' ),
		);

		$this->messages = $messages ? $messages : tribe( 'tickets.rest-v1.messages' );
	}

	/**
	 * Retrieves an array representation of the post.
	 *
	 * @since 4.7.5
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
	 * Returns ticket id
	 *
	 * @todo   add return ticket based on service provider
	 *
	 * @since  4.7.5
	 *
	 * @param int    $ticket_id A ticket post ID.
	 * @param string $context   Context of data.
	 *
	 * @return $ticket_id.
	 *
	 */
	public function get_ticket_data( $ticket_id, $context = '' ) {

		$ticket = get_post( $ticket_id );

		if ( empty( $ticket ) || $ticket->post_type !== Tribe__Tickets__RSVP::ATTENDEE_OBJECT ) {
			return new WP_Error( 'ticket-not-found', $this->messages->get_message( 'ticket-not-found' ) );
		}

		$data = array(
			'id'     => $ticket_id,
			'status' => $ticket->post_status,
		);

		/**
		 * Filters the data that will be returned if for a single ticket.
		 *
		 * @since  4.7.5
		 *
		 * @param array   $data  The data that will be returned in the response.
		 * @param WP_Post $event The requested ticket.
		 */
		$data = apply_filters( 'tribe_tickets_rest_ticket_data', $data, $ticket );

		return $data;
	}

}