<?php

class Tribe__Tickets__REST__V1__Endpoints__Single_Attendee
	extends Tribe__Tickets__REST__V1__Endpoints__Base
	implements Tribe__REST__Endpoints__READ_Endpoint_Interface,
	Tribe__Documentation__Swagger__Provider_Interface {

	/**
	 * {@inheritdoc}
	 */
	public function get_documentation() {
		$GET_defaults = array( 'in' => 'query', 'default' => '', 'type' => 'string' );

		return array(
			'get' => array(
				'parameters' => $this->swaggerize_args( $this->READ_args(), $GET_defaults ),
				'responses'  => array(
					'200' => array(
						'description' => __( 'Returns the data of the attendee with the specified post ID', 'ticket-tickets' ),
						'content'     => array(
							'application/json' => array(
								'schema' => array(
									'$ref' => '#/components/schemas/Attendee',
								),
							),
						),
					),
					'400' => array(
						'description' => __( 'The attendee post ID is invalid.', 'ticket-tickets' ),
						'content'     => array(
							'application/json' => array(
								'schema' => array(
									'type' => 'object',
								),
							),
						),
					),
					'401' => array(
						'description' => __( 'The attendee with the specified ID is not accessible.', 'ticket-tickets' ),
						'content'     => array(
							'application/json' => array(
								'schema' => array(
									'type' => 'object',
								),
							),
						),
					),
					'404' => array(
						'description' => __( 'An attendee with the specified ID does not exist.', 'ticket-tickets' ),
						'content'     => array(
							'application/json' => array(
								'schema' => array(
									'type' => 'object',
								),
							),
						),
					),
				),
			),
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function READ_args() {
		return array(
			'id' => array(
				'type'              => 'integer',
				'in'                => 'path',
				'description'       => __( 'The attendee post ID', 'event-tickets' ),
				'required'          => true,
				/**
				 * Here we check for a positive int, not an attendee ID to properly
				 * return 404 for missing post in place of 400.
				 */
				'validate_callback' => array( $this->validator, 'is_positive_int' ),
			),
		);
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since 4.12.0 Returns 401 Unauthorized if Event Tickets Plus is not loaded.
	 */
	public function get( WP_REST_Request $request ) {
		// Early bail: ET Plus must be active to use this endpoint.
		if ( ! class_exists( 'Tribe__Tickets_Plus__Main' ) ) {
			return new WP_REST_Response( __( 'Sorry, Event Tickets Plus must be active to use this endpoint.', 'event-tickets' ), 401 );
		}

		return tribe_attendees( 'restv1' )->by_primary_key( $request['id'] );
	}

	/**
	 * Handles POST requests on the endpoint.
	 *
	 * @param WP_REST_Request $request
	 * @param bool            $return_id Whether the created post ID should be returned or the full response object.
	 *
	 * @return WP_Error|WP_REST_Response|int An array containing the data on success or a WP_Error instance on failure.
	 */
	public function create( WP_REST_Request $request, $return_id = false ) {

		$post_data = $this->prepare_attendee_data( $request );

		if ( is_wp_error( $post_data ) ) {
			return $post_data;
		}

		/** @var Tribe__Tickets__Attendees $attendees */
		$attendees       = tribe( 'tickets.attendees' );
		$attendee_object = $attendees->create_attendee( $post_data['ticket'], $post_data['data'] );

		if ( ! $attendee_object ) {
			return new WP_Error( 'attendee-creation-failed', __( 'Something went wrong! Attendee creation failed.', 'event-tickets' ) );
		}

		$attendee = $post_data['provider']->get_attendee( $attendee_object->ID );
		$response = new WP_REST_Response( $attendee );
		$response->set_status( 201 );

		return $response;
	}

	/**
	 * Returns the content of the `args` array that should be used to register the endpoint
	 * with the `register_rest_route` function.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public function CREATE_args() {
		$args = [
			'ticket_id'             => [
				'required'          => true,
				'validate_callback' => 'tribe_events_product_is_ticket',
				'type'              => 'integer',
				'description'       => __( 'The Ticket ID, where the attendee is registered.', 'event-tickets' ),
			],
			'name'                  => [
				'required'          => true,
				'type'              => 'string',
				'description'       => __( 'Full name of the attendee.', 'event-tickets' ),
			],
			'email'                 => [
				'required'          => true,
				'validate_callback' => 'is_email',
				'type'              => 'email',
				'description'       => __( 'Email of the attendeee', 'event-tickets' ),
			],
			'order_status'          => [
				'required'          => false,
				'validate_callback' => 'is_string',
				'type'              => 'string',
				'description'       => __( 'Order Status for the attendee.', 'event-tickets' ),
			],

		];

		return apply_filters( 'tribe_ticket_rest_api_post_attendee_args', $args );
	}

	/**
	 * Handles Update requests on the endpoint.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response|int An array containing the data on success or a WP_Error instance on failure.
	 */
	public function update( WP_REST_Request $request ) {

		$post_data = $this->prepare_update_attendee_data( $request );

		if ( is_wp_error( $post_data ) ) {
			return $post_data;
		}

		$provider = tribe_tickets_get_ticket_provider( $post_data['attendee_id'] );

		/** @var Tribe__Tickets__Attendees $attendees */
		$attendees       = tribe( 'tickets.attendees' );
		$attendee_object = $attendees->update_attendee( $post_data['attendee'], $post_data['data'] );

		if ( ! $attendee_object ) {
			return new WP_Error( 'attendee-update-failed', __( 'Something went wrong! Attendee update failed.', 'event-tickets' ) );
		}

		$attendee = $provider->get_attendee( $post_data['attendee_id'] );
		$response = new WP_REST_Response( $attendee );
		$response->set_status( 201 );

		return $response;
	}

	/**
	 * Returns the content of the `args` array that should be used to register the endpoint
	 * with the `register_rest_route` function.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public function EDIT_args() {
		$args = [
			'id' => [
				'type'              => 'integer',
				'in'                => 'path',
				'description'       => __( 'The attendee post ID', 'event-tickets' ),
				'required'          => true,
			],
		];

		return apply_filters( 'tribe_ticket_rest_api_edit_attendee_args', $args );
	}

	/**
	 * Process Request data.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return array|WP_Error
	 */
	public function prepare_attendee_data( WP_REST_Request $request ) {

		$ticket_id = (int) $request->get_param( 'ticket_id' );
		$provider  = tribe_tickets_get_ticket_provider( $ticket_id );

		if ( ! $provider ) {
			return new WP_Error( 'invalid-provider', __( 'Ticket Provider not found.', 'event-tickets' ) );
		}

		// Set up the attendee data for the creation/save.
		$attendee_data = [
			'full_name'       => $request->get_param( 'name' ),
			'email'           => $request->get_param( 'email' ),
			'attendee_source' => 'rest-api',
			'attendee_status' => $request->get_param( 'order_status' ),
		];

		/**
		 * Filter REST API attendee data before creating an attendee.
		 *
		 * @since TBD
		 *
		 * @param array $attendee_data Attendee data.
		 * @param WP_REST_Request $request Request object.
		 */
		$attendee_data = apply_filters( 'tribe_tickets_rest_api_post_attendee_data', $attendee_data, $request );

		return [
			'ticket'   => $ticket_id,
			'provider' => $provider,
			'data'     => $attendee_data,
		];
	}

	/**
	 * Process Request data for updating an attendee.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return array|WP_Error
	 */
	public function prepare_update_attendee_data( WP_REST_Request $request ) {

		$attendee_id = (int) $request->get_param( 'id' );
		$found       = tribe_attendees()->by( 'id', $attendee_id )->found();

		if ( ! $found ) {
			return new WP_Error( 'invalid-attendee-id', __( 'Attendee ID is not valid.', 'event-tickets' ) );
		}

		$provider = tribe_tickets_get_ticket_provider( $attendee_id );

		if ( ! $provider ) {
			return new WP_Error( 'invalid-attendee-provider', __( 'Attendee provider not found.', 'event-tickets' ) );
		}

		$attendee = $provider->get_attendee( $attendee_id );

		// Set up the attendee data for the update.
//		$new_data = [
//			'attendee_source' => 'rest-api',
//		];

//		if ( ! empty( $request['name'] ) ) {
//			$new_data['full_name'] = $request['name'];
//		}
//
//		if ( ! empty( $request['email'] ) ) {
//			$new_data['email'] = $request['email'];
//		}
//
//		if ( ! empty( $request['order_status'] ) ) {
//			$new_data['attendee_status'] = $request['order_status'];
//		}

		$updated_data = $request->get_params();
//		$new_data['attendee_id'] = $attendee_id;

//		unset( $new_data['id'] );

//		$updated_data = wp_parse_args( $new_data, $attendee );

		/**
		 * Filter REST API attendee data before creating an attendee.
		 *
		 * @since TBD
		 *
		 * @param array $attendee_data Attendee data.
		 * @param WP_REST_Request $request Request object.
		 */
		$attendee_data = apply_filters( 'tribe_tickets_rest_api_update_attendee_data', $updated_data, $request );

		return [
			'attendee_id' => $attendee_id,
			'attendee'    => $attendee,
			'data'        => $attendee_data,
		];
	}
}
