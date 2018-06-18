<?php

class Tribe__Tickets__REST__V1__Endpoints__Single_Ticket
	extends Tribe__Tickets__REST__V1__Endpoints__Base
	implements Tribe__REST__Endpoints__READ_Endpoint_Interface,
	Tribe__Documentation__Swagger__Provider_Interface {

	/**
	 * @var Tribe__Tickets__REST__Interfaces__Post_Repository
	 */
	protected $post_repository;
	/**
	 * @var Tribe__Tickets__REST__V1__Validator__Interface
	 */
	protected $validator;

	public function __construct(
		Tribe__REST__Messages_Interface $messages,
		Tribe__Tickets__REST__Interfaces__Post_Repository $post_repository,
		Tribe__Tickets__REST__V1__Validator__Interface $validator
	) {

		parent::__construct( $messages );
		$this->post_repository = $post_repository;
		$this->validator       = $validator;
	}

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

		$cap = get_post_type_object( $ticket_post->post_type )->cap->read_post;

		if ( ! ( 'publish' === $ticket_post->post_status || current_user_can( $cap, $ticket_id ) ) ) {
			$message = $this->messages->get_message( 'ticket-not-accessible' );

			return new WP_Error( 'tickets-not-accessible', $message, array( 'status' => 401 ) );
		}

		return $this->post_repository->get_ticket_data( $ticket_id );
	}

	/**
	 * {@inheritdoc}
	 */
	public function READ_args() {
		return array(
			'id' => array(
				'in'                => 'path',
				'type'              => 'integer',
				'description'       => __( 'the ticket post ID', 'event-tickets' ),
				'required'          => true,
				'validate_callback' => array( $this->validator, 'is_positive_int' ),
			),
		);
	}
}