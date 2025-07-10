<?php
/**
 * Class Tribe__Tickets__REST__V1__Endpoints__QR
 *
 * @since 5.7.0
 *
 * @package Tribe\Tickets\REST\V1\Endpoints\QR
 */

use Tribe__Tickets__Tickets as Tickets;
use Tribe__Events__Main as TEC;


/**
 * Class Tribe__Tickets__REST__V1__Endpoints__QR.
 *
 * @since 5.7.0
 *
 * @package Tribe\Tickets\REST\V1\Endpoints\QR
 */
class Tribe__Tickets__REST__V1__Endpoints__QR extends Tribe__Tickets__REST__V1__Endpoints__Base implements Tribe__REST__Endpoints__READ_Endpoint_Interface, Tribe__Documentation__Swagger__Provider_Interface {

	/**
	 * @var Tribe__REST__Main
	 */
	protected $main;

	/**
	 * @var WP_REST_Request
	 */
	protected $serving;

	/**
	 * @var Tribe__Tickets__REST__Interfaces__Post_Repository
	 */
	protected $post_repository;

	/**
	 * @var Tribe__Tickets__REST__V1__Validator__Interface
	 */
	protected $validator;

	/**
	 * Get attendee by id
	 *
	 * @since 5.7.0
	 *
	 * @param WP_REST_Request $request The request.
	 *
	 * @return mixed|void|WP_Error|WP_REST_Response
	 */
	public function get( WP_REST_Request $request ) {
		$this->serving = $request;

		$ticket      = get_post( $request['id'] );
		$ticket_type = tribe( 'tickets.data_api' )->detect_by_id( $request['id'] );

		$cap = get_post_type_object( $ticket_type['post_type'] )->cap->read_post;
		if ( ! ( 'publish' === $ticket->post_status || current_user_can( $cap, $request['id'] ) ) ) {
			$message = $this->messages->get_message( 'ticket-not-accessible' );

			return new WP_Error( 'ticket-not-accessible', $message, [ 'status' => 403 ] );
		}

		$data = $this->post_repository->get_qr_data( $request['id'], 'single' );

		/**
		 * Filters the data that will be returned for a single qr ticket request.
		 *
		 * @since 4.5.13
		 * @deprecated 5.7.0 Use `tribe_tickets_rest_qr_data` instead.
		 *
		 * @param array           $data    The retrieved data.
		 * @param WP_REST_Request $request The original request.
		 */
		$data = apply_filters_deprecated( 'tribe_tickets_plus_rest_qr_data', [ $data, $request ], '5.7.0', 'tribe_tickets_rest_qr_data' );

		/**
		 * Filters the data that will be returned for a single qr ticket request.
		 *
		 * @since 5.7.0
		 *
		 * @param array           $data    The retrieved data.
		 * @param WP_REST_Request $request The original request.
		 */
		$data = apply_filters( 'tribe_tickets_rest_qr_data', $data, $request );

		return is_wp_error( $data ) ? $data : new WP_REST_Response( $data );
	}

	/**
	 * Returns an array in the format used by Swagger 2.0.
	 *
	 * While the structure must conform to that used by v2.0 of Swagger the structure can be that of a full document
	 * or that of a document part.
	 * The intelligence lies in the "gatherer" of information rather than in the single "providers" implementing this
	 * interface.
	 *
	 * @since 5.7.0
	 *
	 * @link http://swagger.io/
	 *
	 * @return array An array description of a Swagger supported component.
	 */
	public function get_documentation() {
		$post_defaults = [
			'in'      => 'formData',
			'default' => '',
			'type'    => 'string',
		];
		$post_args     = array_merge( $this->READ_args(), $this->CHECK_IN_args() );

		return [
			'post' => [
				'consumes'   => [ 'application/x-www-form-urlencoded' ],
				'parameters' => $this->swaggerize_args( $post_args, $post_defaults ),
				'responses'  => [
					'201' => [
						'description' => __( 'Returns successful check in', 'event-tickets' ),
						'schema'      => [
							'$ref' => '#/definitions/Ticket',
						],
					],
					'400' => [
						'description' => __( 'A required parameter is missing or an input parameter is in the wrong format', 'event-tickets' ),
					],
					'403' => [
						'description' => esc_html(
							sprintf(
								// Translators: %s is the 'ticket' label (singular, lowercase).
								__( 'The %s is already checked in', 'event-tickets' ),
								tribe_get_ticket_label_singular_lowercase( 'rest_qr' )
							)
						),
					],
				],
			],
		];
	}

	/**
	 * Provides the content of the `args` array to register the endpoint support for GET requests.
	 *
	 * @since 5.7.0
	 *
	 * @return array
	 */
	public function READ_args() {
		return [
			'id' => [
				'in'                => 'path',
				'type'              => 'integer',
				'description'       => esc_html(
					sprintf(
						// Translators: %s is the 'ticket' label (singular, lowercase).
						__( 'The %s id.', 'event-tickets' ),
						tribe_get_ticket_label_singular_lowercase( 'rest_qr' )
					)
				),
				'required'          => true,
				'validate_callback' => [ $this->validator, 'is_ticket_id' ],
			],
		];
	}

	/**
	 * Returns the content of the `args` array that should be used to register the endpoint
	 * with the `register_rest_route` function.
	 *
	 * @since 5.7.0
	 *
	 * @return array
	 */
	public function CHECK_IN_args() {
		$ticket_label_singular_lower = esc_html( tribe_get_ticket_label_singular_lowercase( 'rest_qr' ) );

		return [
			// QR fields.
			'api_key'       => [
				'required'          => true,
				'validate_callback' => [ $this->validator, 'is_string' ],
				'type'              => 'string',
				'description'       => __( 'The API key to authorize check in.', 'event-tickets' ),
			],
			'ticket_id'     => [
				'required'          => true,
				'validate_callback' => [ $this->validator, 'is_numeric' ],
				'type'              => 'string',
				'description'       => esc_html(
					sprintf(
						// Translators: %s is the 'ticket' label (singular, lowercase).
						__( 'The ID of the %s to check in.', 'event-tickets' ),
						$ticket_label_singular_lower
					)
				),
			],
			'security_code' => [
				'required'          => true,
				'validate_callback' => [ $this->validator, 'is_string' ],
				'type'              => 'string',
				'description'       => esc_html(
					sprintf(
						// Translators: %s is the 'ticket' label (singular, lowercase).
						__( 'The security code of the %s to verify for check in.', 'event-tickets' ),
						$ticket_label_singular_lower
					)
				),
			],
		];
	}

	/**
	 * Check in attendee.
	 *
	 * @since 5.7.0
	 *
	 * @param WP_REST_Request $request The request.
	 *
	 * @return WP_REST_Response
	 */
	public function check_in( WP_REST_Request $request ) {
		$this->serving = $request;

		$qr_arr = $this->prepare_qr_arr( $request );

		if ( is_wp_error( $qr_arr ) ) {
			$response = new WP_REST_Response( $qr_arr );
			$response->set_status( 400 );

			return $response;
		}

		$api_key_is_valid = $this->has_api( $qr_arr );

		/**
		 * Allow filtering the API key validation status.
		 *
		 * @since 5.2.5
		 *
		 * @param bool  $is_valid Whether the provided API key is valid or not.
		 * @param array $qr_arr The request data for Check in.
		 */
		$api_key_is_valid = apply_filters_deprecated( 'event_tickets_plus_requested_api_is_valid', [ $api_key_is_valid, $qr_arr ], '5.7.0', 'tec_tickets_requested_api_is_valid' );

		/**
		 * Allow filtering the API key validation status.
		 *
		 * @since 5.7.0
		 *
		 * @param bool  $is_valid Whether the provided API key is valid or not.
		 * @param array $qr_arr The request data for Check in.
		 */
		$api_key_is_valid = apply_filters( 'tec_tickets_requested_api_is_valid', $api_key_is_valid, $qr_arr );

		// Check all the data we need is there.
		if ( empty( $api_key_is_valid ) || empty( $qr_arr['ticket_id'] ) ) {
			$response = new WP_REST_Response( $qr_arr );
			$response->set_status( 400 );

			return $response;
		}

		$event_id      = (int) $qr_arr['event_id'];
		$attendee_id   = (int) $qr_arr['ticket_id'];
		$security_code = (string) $qr_arr['security_code'];

		/** @var Tribe__Tickets__Data_API $data_api */
		$data_api = tribe( 'tickets.data_api' );

		$ticket_provider = $data_api->get_ticket_provider( $attendee_id );
		if (
			empty( $ticket_provider->security_code )
			|| get_post_meta( $attendee_id, $ticket_provider->security_code, true ) !== $security_code
		) {
			$response = new WP_REST_Response(
				[
					'msg'   => __( 'Security code is not valid!', 'event-tickets' ),
					'error' => 'security_code_not_valid',
				]
			);
			$response->set_status( 403 );

			return $response;
		}

		// Add check for attendee data.
		$attendee = $ticket_provider->get_attendees_by_id( $attendee_id );
		$attendee = reset( $attendee );
		if ( ! is_array( $attendee ) ) {
			$response = new WP_REST_Response(
				[
					'msg'   => __( 'An attendee is not found with this ID.', 'event-tickets' ),
					'error' => 'attendee_not_found',
				]
			);
			$response->set_status( 403 );

			return $response;
		}

		// Get the attendee data to populate the response.
		$attendee_data = tribe( 'tickets.rest-v1.attendee-repository' )->format_item( $attendee_id );

		/**
		 * Filters the Attendee data for the QR check-in.
		 *
		 * @since 5.16.0
		 *
		 * @param array<string,mixed> $attendee_data The Attendee data.
		 * @param int                 $attendee_id   The Attendee ID.
		 * @param int                 $event_id      The ID of the post this Attendee is being checked into.
		 * @param Tickets             $ticket_provider The Ticket provider.
		 */
		$attendee_data = apply_filters(
			'tec_tickets_qr_checkin_attendee_data',
			$attendee_data,
			$attendee_id,
			$event_id,
			$ticket_provider
		);

		/** @var Tribe__Tickets__Status__Manager $status */
		$status = tribe( 'tickets.status' );

		$complete_statuses = (array) $status->get_completed_status_by_provider_name( $ticket_provider );

		if ( ! in_array( $attendee['order_status'], $complete_statuses, true ) ) {
			$response = new WP_REST_Response(
				[
					'msg'      => esc_html(
						sprintf(
							// Translators: %s: 'ticket' label (singular, lowercase).
							__( "This attendee's %s is not authorized to be Checked in. Please check the order status.", 'event-tickets' ),
							tribe_get_ticket_label_singular_lowercase( 'rest_qr' )
						)
					),
					'error'    => 'attendee_not_authorized',
					'attendee' => $attendee_data,
				]
			);

			$response->set_status( 403 );

			return $response;
		}

		// Check if the attendee is checked in.
		$checked_status = get_post_meta( $attendee_id, '_tribe_qr_status', true );

		if ( ! $checked_status ) {
			$checked_status = get_post_meta( $attendee_id, $ticket_provider->checkin_key, true );
		}

		if ( $checked_status ) {
			$response = new WP_REST_Response(
				[
					'msg'      => __( 'Already checked in!', 'event-tickets' ),
					'error'    => 'attendee_already_checked_in',
					'attendee' => $attendee_data,
				]
			);
			$response->set_status( 403 );

			return $response;
		}

		// Check if TEC is enabled, and if we want to only check in when the event is happening.
		if ( $this->should_checkin_qr_events_happening_now( $event_id, $attendee_id ) ) {

			// Check if the current event is on date and time.
			if ( ! $this->is_tec_event_happening_now( $event_id ) ) {

				$response = new WP_REST_Response(
					[
						'msg'      => __( 'Event has not started or it has finished.', 'event-tickets' ),
						'error'    => 'event_not_happening_now',
						'attendee' => $attendee_data,
					]
				);

				$response->set_status( 403 );

				return $response;
			}
		}

		$checked = $this->do_check_in( $attendee_id, $event_id, $ticket_provider );

		if ( ! $checked ) {
			$msg_arr = [
				'msg'             => esc_html(
					sprintf(
						// Translators: %s: 'ticket' label (singular, lowercase).
						__( '%s not checked in!', 'event-tickets' ),
						tribe_get_ticket_label_singular( 'rest_qr' )
					)
				),
				'error'           => 'attendee_failed_check_in',
				'tribe_qr_status' => get_post_meta( $attendee_id, '_tribe_qr_status', 1 ),
				'attendee'        => $attendee_data,
			];
			$result  = array_merge( $msg_arr, $qr_arr );

			$response = new WP_REST_Response( $result );
			$response->set_status( 403 );

			/**
			 * Filters the REST Response returned on failure to check in an Attendee via QR code.
			 *
			 * @since 5.8.2
			 *
			 * @param WP_REST_Response $response        The failure response, as prepared following the default logic.
			 * @param int              $attendee_id     The post ID of the Attendee to check in.
			 * @param int              $event_id        The ID of the ticket-able post the Attendee is trying to check-in
			 *                                          to. While the name would suggest so, this can be the ID of any post
			 *                                          type, not just Events.
			 * @param Tickets          $ticket_provider The commerce module used by the Attendee.
			 */
			$response = apply_filters(
				'tec_tickets_qr_checkin_failure_rest_response',
				$response,
				$attendee_id,
				$event_id,
				$ticket_provider
			);

			return $response;
		}

		$response = new WP_REST_Response(
			[
				'msg'      => __( 'Checked In!', 'event-tickets' ),
				'attendee' => $attendee_data,
			]
		);
		$response->set_status( 201 );

		return $response;
	}

	/**
	 * Check if the QR codes should be only checked when the event is on date and time.
	 *
	 * @since 5.7.0
	 *
	 * @param int $event_id    The ID of the event.
	 * @param int $attendee_id The ID of the current attendee of the QR code.
	 *
	 * @return boolean True if it should be checking-in tickets for events that are on date and time.
	 */
	public function should_checkin_qr_events_happening_now( $event_id, $attendee_id ): bool {
		// Bail if TEC is not active.
		if ( ! tec_tickets_tec_events_is_active() ) {
			return false;
		}

		// Bail if `tribe_events` CPT is not enabled to have tickets.
		$enabled_post_types = (array) tribe_get_option( 'ticket-enabled-post-types', [] );
		if ( ! in_array( TEC::POSTTYPE, $enabled_post_types, true ) ) {
			return false;
		}

		$should_checkin_qr_events_happening_now = (bool) tribe_get_option( 'tickets-plus-qr-check-in-events-happening-now', false );

		/**
		 * Filter the option for QR codes to be only checked in when an event is happening.
		 *
		 * @since 5.6.2
		 * @deprecated 5.7.0 Use `tec_tickets_qr_checkin_events_happening_now` instead.
		 *
		 * @param bool $should_checkin_qr_events_happening_now True if it should check in QR codes on events that are on date an time.
		 * @param int  $event_id                          The ID of the event, from the current attendee of the QR code.
		 * @param int  $attendee_id                       The ID of the current attendee of the QR code.
		 */
		$should_checkin_qr_events_happening_now = (bool) apply_filters_deprecated(
			'tec_tickets_plus_qr_checkin_events_happening_now',
			[ $should_checkin_qr_events_happening_now, $event_id, $attendee_id ],
			'5.7.0',
			'tec_tickets_qr_checkin_events_happening_now'
		);

		/**
		 * Filter the option for QR codes to be only checked in when an event is happening.
		 *
		 * @since 5.7.0
		 *
		 * @param bool $should_checkin_qr_events_happening_now True if it should check in QR codes on events that are on date an time.
		 * @param int  $event_id                          The ID of the event, from the current attendee of the QR code.
		 * @param int  $attendee_id                       The ID of the current attendee of the QR code.
		 */
		$should_checkin_qr_events_happening_now = (bool) apply_filters( 'tec_tickets_qr_checkin_events_happening_now', $should_checkin_qr_events_happening_now, $event_id, $attendee_id );

		return $should_checkin_qr_events_happening_now;
	}

	/**
	 * Check if an event is on date and time, in order to check-in QR codes.
	 *
	 * @since 5.7.0
	 *
	 * @param int $event_id The Event ID.
	 *
	 * @return boolean True if the Event is on date and time.
	 */
	public function is_tec_event_happening_now( $event_id ): bool {
		// Get the event.
		$event = tribe_get_event( $event_id );

		// Bail if it's empty or if the ticket is from a page/post or any other CPT with tickets.
		if ( empty( $event ) || TEC::POSTTYPE !== $event->post_type ) {
			return true;
		}

		// Get the time buffer option.
		$time_buffer = (int) tribe_get_option( 'tickets-plus-qr-check-in-events-happening-now-time-buffer', 0 );

		/**
		 * Filter the time buffer, in minutes.
		 * This buffer is for QR check-ins when it's set to only check-in when the event is on date and time.
		 *
		 * @since 5.6.2
		 * @deprecated 5.7.0 Use `tec_tickets_qr_checkin_events_happening_now_buffer` instead.
		 *
		 * @param int $buffer   The time buffer in minutes.
		 * @param int $event_id The event ID.
		 */
		$time_buffer = (int) apply_filters_deprecated(
			'tec_tickets_plus_qr_checkin_events_happening_now_buffer',
			[ $time_buffer, $event_id ],
			'5.7.0',
			'tec_tickets_qr_checkin_events_happening_now_buffer'
		);

		/**
		 * Filter the time buffer, in minutes.
		 * This buffer is for QR check-ins when it's set to only check-in when the event is on date and time.
		 *
		 * @since 5.7.0
		 *
		 * @param int $buffer   The time buffer in minutes.
		 * @param int $event_id The event ID.
		 */
		$time_buffer      = (int) apply_filters( 'tec_tickets_qr_checkin_events_happening_now_buffer', $time_buffer, $event_id );
		$time_buffer      = ! empty( $time_buffer ) ? $time_buffer : 0;
		$time_buffer_text = 'PT' . $time_buffer . 'M';

		// Set up the dates for the event, with the corresponding timezone and buffer.
		$now   = Tribe__Date_Utils::build_date_object( 'now', $event->timezone );
		$start = $event->dates->start->sub( new DateInterval( $time_buffer_text ) );
		$end   = $event->dates->end->add( new DateInterval( $time_buffer_text ) );

		// Return if the event is happening now.
		return $now >= $start && $now <= $end;
	}

	/**
	 * Check if API is present and matches key is settings
	 *
	 * @since 5.7.0
	 *
	 * @param array $qr_arr Array of QR data.
	 *
	 * @return bool
	 */
	public function has_api( $qr_arr ): bool {
		if ( empty( $qr_arr['api_key'] ) ) {
			return false;
		}

		$tec_options = Tribe__Settings_Manager::get_options();
		if ( ! is_array( $tec_options ) ) {
			return false;
		}

		if ( esc_attr( $qr_arr['api_key'] ) !== $tec_options['tickets-plus-qr-options-api-key'] ) {
			return false;
		}

		return true;
	}

	/**
	 * Setup array of variables for check in
	 *
	 * @since 5.7.0
	 *
	 * @param WP_REST_Request $request The request.
	 *
	 * @return array|mixed|void
	 */
	protected function prepare_qr_arr( WP_REST_Request $request ) {
		$qr_arr = [
			'api_key'       => $request['api_key'],
			'ticket_id'     => $request['ticket_id'],
			'event_id'      => $request['event_id'],
			'security_code' => $request['security_code'],
		];

		/**
		 * Allow filtering of $postarr data with additional $request arguments.
		 *
		 * @param array           $qr_arr  Post array used for check in
		 * @param WP_REST_Request $request REST request object
		 *
		 * @since 4.7.5
		 * @deprecated 5.7.0 Use `tribe_tickets_rest_qr_prepare_qr_arr` instead.
		 */
		$qr_arr = apply_filters_deprecated(
			'tribe_tickets_plus_rest_qr_prepare_qr_arr',
			[ $qr_arr, $request ],
			'5.7.0',
			'tribe_tickets_rest_qr_prepare_qr_arr'
		);

		/**
		 * Allow filtering of $postarr data with additional $request arguments.
		 *
		 * @param array           $qr_arr  Post array used for check in
		 * @param WP_REST_Request $request REST request object
		 *
		 * @since 5.7.0
		 */
		$qr_arr = apply_filters( 'tribe_tickets_rest_qr_prepare_qr_arr', $qr_arr, $request );

		return $qr_arr;
	}

	/**
	 * Check in attendee and on first success return
	 *
	 * @since 5.7.0
	 * @since 5.16.0 Changed method name from `_check_in` to `do_check_in`.
	 *
	 * @param int     $attendee_id The attendee ID.
	 * @param int     $event_id The ID of the ticketable post the Attendee is being checked into.
	 * @param Tickets $ticket_provider The Attendee ticket provider.
	 *
	 * @return boolean Whether the check in was successful or not.
	 */
	private function do_check_in( $attendee_id, $event_id, $ticket_provider ) {
		if ( empty( $ticket_provider ) ) {
			return false;
		}

		// Set parameter to true for the QR app - it is false for the original url so that the message displays.
		$success = $ticket_provider->checkin( $attendee_id, true, $event_id );
		if ( $success ) {
			return $success;
		}

		return false;
	}
}
