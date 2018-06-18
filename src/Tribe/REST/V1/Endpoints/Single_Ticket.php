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