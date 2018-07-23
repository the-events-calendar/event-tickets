<?php

class Tribe__Tickets__REST__V1__Endpoints__Ticket_Archive
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
		// @todo - implement me!
		return array();
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
		$per_page   = (int) $request->get_param( 'per_page' );
		$page       = (int) $request->get_param( 'page' );

		$fetch_args = array();

		$supported_args = array(
			'search'       => 's',
			'include_post' => 'event',
			'is_available' => 'is_available',
			'provider'     => 'provider',
		);

		foreach ( $supported_args as $request_arg => $query_arg ) {
			if ( isset( $request[ $request_arg ] ) ) {
				$fetch_args[ $query_arg ] = $request[ $request_arg ];
			}
		}

		if ( current_user_can( 'read_private_posts' ) ) {
			$permission                = Tribe__Tickets__REST__V1__Ticket_Repository::PERMISSION_EDITABLE;
			$fetch_args['post_status'] = 'any';
		} else {
			$permission                = Tribe__Tickets__REST__V1__Ticket_Repository::PERMISSION_READABLE;
			$fetch_args['post_status'] = 'publish';
		}

		$query = tribe_tickets( 'restv1' )
			->by_args( $fetch_args )
			->permission( $permission );

		if ( $request['order'] ) {
			$query->order( $request['order'] );
		}

		if ( $request['orderby'] ) {
			$query->order_by( $request['orderby'] );
		}

		if ( $request['offset'] ) {
			$query->offset( $request['offset'] );
		}

		$found = $query->found();

		if ( 0 === $found && 1 === $page ) {
			$tickets = array();
		} elseif ( 1 !== $page && $page * $per_page > $found ) {
			return new WP_Error( 'invalid-page-number', $this->messages->get_message( 'invalid-page-number' ), array( 'status' => 400 ) );
		} else {
			$tickets = $query
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

		$data['rest_url']    = add_query_arg( $query_args, $main->get_url( '/tickets/' ) );
		$data['total']       = $found;
		$data['total_pages'] = (int) ceil( $found / $per_page );
		$data['tickets']     = $tickets;

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
		// @todo add all the other args
		return array(
			'page'         => array(
				'description'       => __( 'The page of results to return; defaults to 1', 'event-tickets' ),
				'type'              => 'integer',
				'default'           => 1,
				'sanitize_callback' => 'absint',
				'minimum'           => 1,
			),
			'per_page'     => array(
				'description'       => __( 'How many tickets to return per results page; defaults to posts_per_page.', 'event-tickets' ),
				'type'              => 'integer',
				'default'           => get_option( 'posts_per_page' ),
				'minimum'           => 1,
				'maximum'           => 100,
				'sanitize_callback' => 'absint',
			),
			'search'   => array(
				'description'       => __( 'Limit results to tickets containing the specified string in the title or description.', 'event-tickets' ),
				'type'              => 'string',
				'required'          => false,
				'validate_callback' => array( $this->validator, 'is_string' ),
			),
			'offset'  => array(
				'description' => __( 'Offset the results by a specific number of items.', 'event-tickets' ),
				'type'        => 'integer',
				'required'    => false,
				'min'         => 0,
			),
			'order' => array(
				'description' => __( 'Sort results in ASC or DESC order. Defaults to ASC.', 'event-tickets' ),
				'type'        => 'string',
				'required'    => false,
				'enum'        => array(
					'ASC',
					'DESC',
				),
			),
			'orderby' => array(
				'description' => __( 'Order the results by one of date, relevance, id, include, title, or slug; defaults to title.', 'event-tickets' ),
				'type'        => 'string',
				'required'    => false,
				'enum'        => array(
					'id',
					'include',
					'title',
					'slug',
				),
			),
			'is_available' => array(
				'description' => __( 'Limit results to tickets that have or do not have capacity currently available.', 'event-tickets' ),
				'type'        => 'boolean',
				'required'    => false,
			),
			'provider' => array(
				'description'       => __( 'Limit results to tickets provided by one of the providers specified in the CSV list or array; defaults to all available.', 'event-tickets' ),
				'required'          => false,
				'sanitize-callback' => array( 'Tribe__Utils__Array', 'list_to_array' ),
			),
			'include_post' => array(
				// @todo support multiple types in Swaggerification functions
				// 'swagger_type' => array('integer', 'array', 'string'),
				'swagger_type'      => 'string',
				'description'       => __( 'Limit results to tickets that are assigned to one of the posts specified in the CSV list or array', 'event-tickets' ),
				'required'          => false,
				'validate_callback' => array( $this->validator, 'is_post_id_list' ),
				'sanitize_callback' => array( $this->validator, 'list_to_array' ),
			),
		);
	}

	/**
	 * Filters the found tickets to only return those the current user can access and formats
	 * the ticket data depending on the current user access rights.
	 *
	 * @since TBD
	 *
	 * @param Tribe__Tickets__Ticket_Object[] $found
	 *
	 * @return array[]
	 */
	protected function filter_readable_tickets( array $found ) {
		$readable = array();

		foreach ( $found as $ticket ) {
			$ticket_id   = $ticket->ID;
			$ticket_data = $this->get_readable_ticket_data( $ticket_id );

			if ( $ticket_data instanceof WP_Error ) {
				continue;
			}

			$readable[] = $ticket_data;
		}


		return $readable;
	}
}
