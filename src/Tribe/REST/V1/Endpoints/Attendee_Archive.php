<?php

class Tribe__Tickets__REST__V1__Endpoints__Attendee_Archive
	extends Tribe__Tickets__REST__V1__Endpoints__Base
	implements Tribe__REST__Endpoints__READ_Endpoint_Interface,
	Tribe__Documentation__Swagger__Provider_Interface {

	/**
	 * Returns an array in the format used by Swagger 2.0.
	 *
	 * While the structure must conform to that used by v2.0 of Swagger the structure can be that of a full document
	 * or that of a document part.
	 * The intelligence lies in the "gatherer" of informations rather than in the single "providers" implementing this
	 * interface.
	 *
	 * @link http://swagger.io/
	 *
	 * @return array An array description of a Swagger supported component.
	 */
	public function get_documentation() {
		// @todo implement this for ticket https://central.tri.be/issues/108024
		return [];
	}

	/**
	 * Handles GET requests on the endpoint.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response An array containing the data on success or a WP_Error instance on failure.
	 */
	public function get( WP_REST_Request $request ) {
		$query_args = $request->get_query_params();
		$page  = $request['page'];
		$per_page = $request['per_page'];

		$fetch_args = array();

		$supported_args = array(
			'provider'       => 'provider',
			'search'         => 's',
			'post_id'        => 'event',
			'ticket_id'      => 'ticket',
			'include_post'   => 'event',
			'include_ticket' => 'ticket',
			'exclude_post'   => 'event__not_in',
			'exclude_ticket' => 'ticket__not_in',
		);

		foreach ( $supported_args as $request_arg => $query_arg ) {
			if ( isset( $request[ $request_arg ] ) ) {
				$fetch_args[ $query_arg ] = $request[ $request_arg ];
			}
		}

		if ( current_user_can( 'read_private_posts' ) ) {
			$permission                = Tribe__Tickets__REST__V1__Repositories__Attendee_Read::PERMISSION_EDITABLE;
			$fetch_args['post_status'] = 'any';
		} else {
			$permission                = Tribe__Tickets__REST__V1__Repositories__Attendee_Read::PERMISSION_READABLE;
			$fetch_args['post_status'] = 'publish';
		}

		$query = tribe_attendees( 'restv1' )
			->fetch()
			->by_args( $fetch_args )
			->permission( $permission );

		$found = $query->found();

		if ( 0 === $found && 1 === $page ) {
			$attendees = array();
		} elseif ( 1 !== $page && $page * $per_page > $found ) {
			return new WP_Error( 'invalid-page-number', $this->messages->get_message( 'invalid-page-number' ), array( 'status' => 400 ) );
		} else {
			$attendees = $query
				->per_page( $per_page )
				->page( $page )
				->all();
		}

		/** @var Tribe__Tickets__REST__V1__Main $main */
		$main = tribe( 'tickets.rest-v1.main' );

		// make sure all arrays are formatted to by CSV lists
		foreach ( $query_args as $key => &$value ) {
			if ( is_array( $value ) ) {
				$value = Tribe__Utils__Array::to_list( $value );
			}
		}

		$data['rest_url']      = add_query_arg( $query_args , $main->get_url( '/attendees/' ) );
		$data['total']         = $found;
		$data['total_pages']   = (int) ceil( $found / $per_page );
		$data['attendees']     = $attendees;

		$headers = array(
			'X-ET-TOTAL'       => $data['total'],
			'X-ET-TOTAL-PAGES' => $data['total_pages'],
		);

		return new WP_REST_Response( $data, 200, $headers );
	}

	/**
	 * Returns the content of the `args` array that should be used to register the endpoint
	 * with the `register_rest_route` function.
	 *
	 * @return array
	 */
	public function READ_args() {
		return array(
			'page'     => array(
				'description'       => __( 'The page of results to return; defaults to 1', 'event-tickets' ),
				'type'              => 'integer',
				'required'          => false,
				'default'           => 1,
				'sanitize_callback' => 'absint',
				'minimum'           => 1,
			),
			'per_page' => array(
				'description'       => __( 'How many attendees to return per results page; defaults to posts_per_page.', 'event-tickets' ),
				'type'              => 'integer',
				'required'          => false,
				'default'           => get_option( 'posts_per_page' ),
				'minimum'           => 1,
				'maximum'           => 100,
				'sanitize_callback' => 'absint',
			),
			'provider' => array(
				'description'       => __( 'Limit results to attendees whose ticket is provided by one of the providers specified in the CSV list or array; defaults to all the available.', 'event-tickets' ),
				'type'              => 'string',
				'required'          => false,
				'validate_callback' => array( $this->validator, 'is_string' ),
				'sanitize_callback' => array( $this->validator, 'trim' ),
			),
			'search'   => array(
				'description'       => __( 'Limit results to attendees containing the specified string in the title or description.', 'event-tickets' ),
				'type'              => 'string',
				'required'          => false,
				'validate_callback' => array( $this->validator, 'is_string' ),
			),
			'post_id'  => array(
				'description'       => __( 'Limit results to attendees by post the ticket is associated with.', 'event-tickets' ),
				'type'              => 'integer',
				'required'          => false,
				'validate_callback' => array( $this->validator, 'is_post_id' ),
			),
			'ticket_id' => array(
				'description'       => __( 'Limit results to attendees associated with a ticket.', 'event-tickets' ),
				'type'              => 'integer',
				'required'          => false,
				'validate_callback' => array( $this->validator, 'is_ticket_id' ),
			),
			// @todo after
			// @todo before
			// @todo include
			// @todo exclude
			// @todo price_max
			// @todo price_minA
			// @todo offset
			// @todo order
			// @todo orderby
			'include_post'   => array(
				'description'       => __( 'Limit results to attendees whose ticket is assigned to one of the posts specified in the CSV list or array.', 'event-tickets' ),
				'required'          => false,
				'validate_callback' => array( $this->validator, 'is_post_id_list' ),
			),
			'exclude_post'   => array(
				'description'       => __( 'Limit results to attendees whose tickets is not assigned to any of the posts specified in the CSV list or array..', 'event-tickets' ),
				'required'          => false,
				'validate_callback' => array( $this->validator, 'is_post_id_list' ),
			),
			'include_ticket' => array(
				'description'       => __( 'Limit results to a specific CSV list or array of ticket IDs.', 'event-tickets' ),
				'required'          => false,
				'validate_callback' => array( $this->validator, 'is_ticket_id_list' ),
			),
			'exclude_ticket' => array(
				'description'       => __( 'Exclude a specific CSV list or array of ticket IDs.', 'event-tickets' ),
				'required'          => false,
				'validate_callback' => array( $this->validator, 'is_ticket_id_list' ),
			),

		);
	}
}