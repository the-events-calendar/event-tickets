<?php

class Tribe__Tickets__REST__V1__Endpoints__Single_Attendee
	extends Tribe__Tickets__REST__V1__Endpoints__Base
	implements Tribe__REST__Endpoints__READ_Endpoint_Interface,
	Tribe__Documentation__Swagger__Provider_Interface {

	/**
	 * {@inheritdoc}
	 */
	public function get_documentation() {
		$GET_defaults = [
			'in'      => 'query',
			'default' => '',
			'type'    => 'string',
		];

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
		return [
			'id' => [
				'type'              => 'integer',
				'in'                => 'path',
				'description'       => __( 'The attendee post ID', 'event-tickets' ),
				'required'          => true,
				/**
				 * Here we check for a positive int, not an attendee ID to properly
				 * return 404 for missing post in place of 400.
				 */
				'validate_callback' => [ $this->validator, 'is_positive_int' ],
			],
		];
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since 4.12.0 Returns 401 Unauthorized if Event Tickets Plus is not loaded.
	 */
	public function get( WP_REST_Request $request ) {
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

		$attendee = tribe_attendees( 'restv1' )->by_primary_key( $attendee_object->ID );
		$response = new WP_REST_Response( $attendee );
		$response->set_status( 201 );

		return $response;
	}

	/**
	 * Returns the content of the `args` array that should be used to register the endpoint
	 * with the `register_rest_route` function.
	 *
	 * @since 5.3.2
	 *
	 * @return array Array of supported arguments for the create endpoint.
	 */
	public function CREATE_args() {
		$args = [
			'ticket_id'             => [
				'required'          => true,
				'validate_callback' => 'tribe_events_product_is_ticket',
				'type'              => 'integer',
				'description'       => __( 'The Ticket ID, where the attendee is registered.', 'event-tickets' ),
			],
			'full_name'             => [
				'required'          => true,
				'type'              => 'string',
				'description'       => __( 'Full name of the attendee.', 'event-tickets' ),
			],
			'email'                 => [
				'required'          => true,
				'validate_callback' => 'is_email',
				'type'              => 'email',
				'description'       => __( 'Email of the attendeee.', 'event-tickets' ),
			],
			'attendee_status'       => [
				'required'          => false,
				'type'              => 'string',
				'description'       => __( 'Order Status for the attendee.', 'event-tickets' ),
			],
			'check_in'              => [
				'required'          => false,
				'type'              => 'bool',
				'description'       => __( 'Check in value for the attendee.', 'event-tickets' ),
			],

		];

		/**
		 * Filters the supported args for the create endpoint.
		 *
		 * @since 5.3.2
		 *
		 * @param array $args Supported list of arguments.
		 */
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

		$attendee = tribe_attendees( 'restv1' )->by_primary_key( $post_data['attendee_id'] );
		$response = new WP_REST_Response( $attendee );
		$response->set_status( 201 );

		return $response;
	}

	/**
	 * Returns the content of the `args` array that should be used to register the endpoint
	 * with the `register_rest_route` function.
	 *
	 * @since 5.3.2
	 *
	 * @return array Array of supported arguments for the edit endpoint.
	 */
	public function EDIT_args() {
		$args = [
			'id' => [
				'type'              => 'integer',
				'in'                => 'path',
				'description'       => __( 'The attendee post ID', 'event-tickets' ),
				'required'          => true,
			],
			'check_in'              => [
				'required'          => false,
				'type'              => 'bool',
				'description'       => __( 'Check in value for the attendee.', 'event-tickets' ),
			],
		];

		/**
		 * Filters the supported args for the edit endpoint.
		 *
		 * @since 5.3.2
		 *
		 * @param array $args Supported list of arguments.
		 */
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

		$attendee_data = $request->get_params();
		$attendee_data[ 'attendee_source' ] = 'rest-api';
		$validate_status = $this->validate_attendee_status( $attendee_data, $provider );

		if ( is_wp_error( $validate_status ) ) {
			return $validate_status;
		}

		/**
		 * Filter REST API attendee data before creating an attendee.
		 *
		 * @since 5.3.2
		 *
		 * @param array $attendee_data Attendee data.
		 * @param WP_REST_Request $request Request object.
		 */
		$attendee_data = apply_filters( 'tribe_tickets_rest_api_post_attendee_data', $attendee_data, $request );

		if ( is_wp_error( $attendee_data ) ) {
			return $attendee_data;
		}

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

		$attendee        = $provider->get_attendee( $attendee_id );
		$updated_data    = $request->get_params();
		$validate_status = $this->validate_attendee_status( $updated_data, $provider );

		if ( is_wp_error( $validate_status ) ) {
			return $validate_status;
		}

		// validate if trying to update the check_in data.
		if ( ! empty( $updated_data['check_in'] ) ) {
			$validate_check_in = $this->validate_check_in( $attendee, $updated_data['check_in'] );
			if ( is_wp_error( $validate_check_in ) ) {
				return $validate_check_in;
			}
		}
		/**
		 * Filter REST API attendee data before creating an attendee.
		 *
		 * @since 5.3.2
		 *
		 * @param array $updated_data Data that needs to be updated.
		 * @param WP_REST_Request $request Request object.
		 * @param array $attendee_data Attendee data that will be updated.
		 */
		$attendee_data = apply_filters( 'tribe_tickets_rest_api_update_attendee_data', $updated_data, $request, $attendee );

		if ( is_wp_error( $attendee_data ) ) {
			return $attendee_data;
		}

		return [
			'attendee_id' => $attendee_id,
			'attendee'    => $attendee,
			'data'        => $attendee_data,
		];
	}

	/**
	 * Validate Attendee status if available.
	 *
	 * @since 5.3.2
	 *
	 * @param array $data Attendee data.
	 * @param Tribe__Tickets__Tickets $provider Provider for the selected ticket.
	 *
	 * @return array | WP_Error
	 */
	public function validate_attendee_status( $data, $provider ) {
		if ( isset( $data['attendee_status'] ) ) {
			$statuses = tribe( 'tickets.status' )->get_statuses_by_action( 'all', $provider );
			if ( ! in_array( $data['attendee_status'], $statuses, true ) ) {
				$error_message  = sprintf(
					// Translators: %s - List of valid statuses.
					__( 'Supported statuses for this attendee are: %s', 'event-tickets' ),
					implode( ' | ', $statuses )
				);
				return new WP_Error( 'invalid-attendee-status', $error_message, [ 'status' => 400 ] );
			}
		}

		return $data;
	}

	/**
	 * Validates the user permission.
	 *
	 * @since 5.3.2
	 * @since 5.5.0 check the REST API permission via centralized method.
	 *
	 * @return bool
	 */
	public function validate_user_permission() {
		return tribe( 'tickets.rest-v1.main' )->request_has_manage_access();
	}

	/**
	 * Validate whether the check_in value is valid for this attendee.
	 *
	 * @since 5.6.5 Validate check-in data before allowing check-in.
	 *
	 * @param $attendee array Attendee data.
	 * @param $check_in bool Check in value.
	 *
	 * @return bool|WP_Error
	 */
	public function validate_check_in( array $attendee, bool $check_in ) {
		if ( ! tribe_is_truthy( $check_in ) ) {
			return true;
		}

		// check if attendee already checked in.
		if ( tribe_is_truthy( $attendee['check_in'] ) ) {
			return new WP_Error( 'tec-et-attendee-already-checked-in', __( 'Attendee is already checked in.', 'event-tickets' ), [ 'status' => 400 ] );
		}

		$provider = $attendee['provider'] ?? tribe_tickets_get_ticket_provider( $attendee['attendee_id'] );

		/** @var Tribe__Tickets__Status__Manager $status */
		$status = tribe( 'tickets.status' );
		$complete_statuses = (array) $status->get_completed_status_by_provider_name( $provider );
		if ( ! in_array( $attendee['order_status'], $complete_statuses, true ) ) {
			return new WP_Error( 'tec-et-attendee-invalid-check-in', __( 'Attendee Order status is not authorized for check-in.', 'event-tickets' ), [ 'status' => 400 ] );
		}

		return $check_in;
	}
}
