<?php

class Tribe__Tickets__REST__V1__Endpoints__Single_Ticket
	extends Tribe__Tickets__REST__V1__Endpoints__Base
	implements Tribe__REST__Endpoints__READ_Endpoint_Interface,
	Tribe__Documentation__Swagger__Provider_Interface {

	/**
	 * {@inheritdoc}
	 */
	public function get_documentation() {
		// @todo implement this for ticket https://central.tri.be/issues/108024
		return array();
	}

	/**
	 * {@inheritdoc}
	 */
	public function get( WP_REST_Request $request ) {
		$ticket_id = $request['id'];

		$ticket_post = get_post( $ticket_id );

		if ( ! $ticket_post instanceof WP_Post ) {
			return new WP_Error( 'ticket-not-found', $this->messages->get_message( 'ticket-not-found' ), array( 'status' => 404 ) );
		}

		$ticket_post_type_object = get_post_type_object( $ticket_post->post_type );
		$read_cap                = $ticket_post_type_object->cap->read_post;
		$read_private_cap        = $ticket_post_type_object->cap->edit_post;

		if ( ! ( 'publish' === $ticket_post->post_status || current_user_can( $read_cap, $ticket_id ) ) ) {
			$message = $this->messages->get_message( 'ticket-not-accessible' );

			return new WP_Error( 'tickets-not-accessible', $message, array( 'status' => 401 ) );
		}

		$context = current_user_can( $read_private_cap, $ticket_id )
			? Tribe__Tickets__REST__V1__Post_Repository::CONTEXT_PUBLIC
			: Tribe__Tickets__REST__V1__Post_Repository::CONTEXT_EDITOR;
		$this->post_repository->set_context( $context );
		$data    = $this->post_repository->get_ticket_data( $ticket_id, $context );

		/**
		 * Filters the data that will be returned for a single ticket request.
		 *
		 * @since TBD
		 *
		 * @param array           $data    The ticket data.
		 * @param WP_REST_Request $request The original request.
		 */
		$data = apply_filters( 'tribe_rest_single_ticket_data', $data, $request );

		return $data;
	}

	/**
	 * {@inheritdoc}
	 */
	public function READ_args() {
		return array(
			'id' => array(
				// @todo update Swaggerification functions to support multiple types
				// 'swagger_type'      => array( 'integer', 'string' ),
				'swagger_type'      => 'string',
				'description'       => __( 'Limit results to tickets that are assigned to one of the posts specified in the CSV list or array', 'event-tickets' ),
				'required'          => false,
				'validate_callback' => array( $this->validator, 'is_positive_int' ),
			),
		);
	}
}