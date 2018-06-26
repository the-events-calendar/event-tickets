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
		// TODO: Implement get_documentation() method.
	}

	/**
	 * Handles GET requests on the endpoint.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response An array containing the data on success or a WP_Error instance on failure.
	 */
	public function get( WP_REST_Request $request ) {
		// @todo here we should support getting all tickets -- ORM API?
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
			'include_post' => array(
				// @todo support multiple types in Swaggerification functions
				// 'swagger_type' => array('integer', 'array', 'string'),
				'swagger_type'      => 'string',
				'description'       => __( 'Limit results to tickets that are assigned to one of the posts specified in the CSV list or array', 'event-tickets' ),
				'required'          => false,
				'validate_callback' => array( $this->validator, 'is_post_id_list' ),
			),
		);
	}
}