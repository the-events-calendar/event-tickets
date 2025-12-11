<?php
/**
 * RSVP V2 REST API Ticket Endpoint.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2\REST
 */

namespace TEC\Tickets\RSVP\V2\REST;

use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_REST_Endpoint;
use TEC\Tickets\RSVP\V2\Ticket;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Class Ticket_Endpoint.
 *
 * Handles RSVP ticket CRUD operations via REST API.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2\REST
 */
class Ticket_Endpoint extends Abstract_REST_Endpoint {

	/**
	 * The REST API endpoint path.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected string $path = '/rsvp/ticket';

	/**
	 * Register the actual endpoint on WP Rest API.
	 *
	 * @since TBD
	 */
	public function register() {
		$namespace     = tribe( 'tickets.rest-v1.main' )->get_events_route_namespace();
		$documentation = tribe( 'tickets.rest-v1.endpoints.documentation' );

		// POST /tribe/tickets/v1/rsvp/ticket - Create RSVP ticket.
		register_rest_route(
			$namespace,
			$this->get_endpoint_path(),
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'create_item' ],
				'permission_callback' => [ $this, 'create_item_permissions_check' ],
				'args'                => $this->get_create_item_args(),
			]
		);

		// PUT /tribe/tickets/v1/rsvp/ticket/{ticket_id} - Update RSVP ticket.
		register_rest_route(
			$namespace,
			$this->get_endpoint_path() . '/(?P<ticket_id>\d+)',
			[
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'update_item' ],
				'permission_callback' => [ $this, 'update_item_permissions_check' ],
				'args'                => $this->get_update_item_args(),
			]
		);

		// DELETE /tribe/tickets/v1/rsvp/ticket/{ticket_id} - Delete RSVP ticket.
		register_rest_route(
			$namespace,
			$this->get_endpoint_path() . '/(?P<ticket_id>\d+)',
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'delete_item' ],
				'permission_callback' => [ $this, 'delete_item_permissions_check' ],
				'args'                => $this->get_delete_item_args(),
			]
		);

		$documentation->register_documentation_provider( $this->get_endpoint_path(), $this );
	}

	/**
	 * Gets the arguments for creating an RSVP ticket.
	 *
	 * @since TBD
	 *
	 * @return array The arguments schema.
	 */
	protected function get_create_item_args(): array {
		return [
			'post_id'        => [
				'required'          => true,
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'description'       => __( 'The post ID to attach the RSVP ticket to.', 'event-tickets' ),
			],
			'name'           => [
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'description'       => __( 'The RSVP ticket name.', 'event-tickets' ),
			],
			'description'    => [
				'required'          => false,
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'wp_kses_post',
				'description'       => __( 'The RSVP ticket description.', 'event-tickets' ),
			],
			'capacity'       => [
				'required'          => false,
				'type'              => 'integer',
				'default'           => -1,
				'sanitize_callback' => [ $this, 'sanitize_capacity' ],
				'description'       => __( 'The RSVP capacity. -1 for unlimited.', 'event-tickets' ),
			],
			'show_not_going' => [
				'required'    => false,
				'type'        => 'boolean',
				'default'     => false,
				'description' => __( 'Whether to show the "Not Going" option.', 'event-tickets' ),
			],
			'start_date'     => [
				'required'          => false,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'description'       => __( 'The RSVP start date (Y-m-d H:i:s format).', 'event-tickets' ),
			],
			'end_date'       => [
				'required'          => false,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'description'       => __( 'The RSVP end date (Y-m-d H:i:s format).', 'event-tickets' ),
			],
		];
	}

	/**
	 * Gets the arguments for updating an RSVP ticket.
	 *
	 * @since TBD
	 *
	 * @return array The arguments schema.
	 */
	protected function get_update_item_args(): array {
		return [
			'ticket_id'      => [
				'required'          => true,
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'description'       => __( 'The ticket ID.', 'event-tickets' ),
			],
			'name'           => [
				'required'          => false,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'description'       => __( 'The RSVP ticket name.', 'event-tickets' ),
			],
			'description'    => [
				'required'          => false,
				'type'              => 'string',
				'sanitize_callback' => 'wp_kses_post',
				'description'       => __( 'The RSVP ticket description.', 'event-tickets' ),
			],
			'capacity'       => [
				'required'          => false,
				'type'              => 'integer',
				'sanitize_callback' => [ $this, 'sanitize_capacity' ],
				'description'       => __( 'The RSVP capacity. -1 for unlimited.', 'event-tickets' ),
			],
			'show_not_going' => [
				'required'    => false,
				'type'        => 'boolean',
				'description' => __( 'Whether to show the "Not Going" option.', 'event-tickets' ),
			],
			'start_date'     => [
				'required'          => false,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'description'       => __( 'The RSVP start date (Y-m-d H:i:s format).', 'event-tickets' ),
			],
			'end_date'       => [
				'required'          => false,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'description'       => __( 'The RSVP end date (Y-m-d H:i:s format).', 'event-tickets' ),
			],
		];
	}

	/**
	 * Gets the arguments for deleting an RSVP ticket.
	 *
	 * @since TBD
	 *
	 * @return array The arguments schema.
	 */
	protected function get_delete_item_args(): array {
		return [
			'ticket_id' => [
				'required'          => true,
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'description'       => __( 'The ticket ID to delete.', 'event-tickets' ),
			],
		];
	}

	/**
	 * Sanitizes capacity value.
	 *
	 * @since TBD
	 *
	 * @param mixed $value The capacity value.
	 *
	 * @return int The sanitized capacity (-1 for unlimited, 0 or positive for limited).
	 */
	public function sanitize_capacity( $value ): int {
		$value = (int) $value;

		return $value < 0 ? -1 : $value;
	}

	/**
	 * Checks permissions for creating an RSVP ticket.
	 *
	 * @since TBD
	 *
	 * @param WP_REST_Request $request The REST request.
	 *
	 * @return bool|WP_Error True if allowed, WP_Error otherwise.
	 */
	public function create_item_permissions_check( $request ) {
		$post_id = $request->get_param( 'post_id' );

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new WP_Error(
				'tec_tickets_rsvp_v2_rest_forbidden',
				__( 'You do not have permission to create tickets for this post.', 'event-tickets' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Checks permissions for updating an RSVP ticket.
	 *
	 * @since TBD
	 *
	 * @param WP_REST_Request $request The REST request.
	 *
	 * @return bool|WP_Error True if allowed, WP_Error otherwise.
	 */
	public function update_item_permissions_check( $request ) {
		$ticket_id = $request->get_param( 'ticket_id' );

		if ( ! current_user_can( 'edit_post', $ticket_id ) ) {
			return new WP_Error(
				'tec_tickets_rsvp_v2_rest_forbidden',
				__( 'You do not have permission to update this ticket.', 'event-tickets' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Checks permissions for deleting an RSVP ticket.
	 *
	 * @since TBD
	 *
	 * @param WP_REST_Request $request The REST request.
	 *
	 * @return bool|WP_Error True if allowed, WP_Error otherwise.
	 */
	public function delete_item_permissions_check( $request ) {
		$ticket_id = $request->get_param( 'ticket_id' );

		if ( ! current_user_can( 'delete_post', $ticket_id ) ) {
			return new WP_Error(
				'tec_tickets_rsvp_v2_rest_forbidden',
				__( 'You do not have permission to delete this ticket.', 'event-tickets' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Handles the request that creates an RSVP ticket.
	 *
	 * @since TBD
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_Error|WP_REST_Response The response on success or a WP_Error instance on failure.
	 */
	public function create_item( WP_REST_Request $request ) {
		$post_id = $request->get_param( 'post_id' );

		// Verify the post exists.
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new WP_Error(
				'tec_tickets_rsvp_v2_invalid_post',
				__( 'Invalid post ID.', 'event-tickets' ),
				[ 'status' => 400 ]
			);
		}

		// Build ticket arguments from request.
		$args = [
			'name' => $request->get_param( 'name' ),
		];

		if ( $request->has_param( 'description' ) ) {
			$args['description'] = $request->get_param( 'description' );
		}

		if ( $request->has_param( 'capacity' ) ) {
			$args['capacity'] = $request->get_param( 'capacity' );
		}

		if ( $request->has_param( 'show_not_going' ) ) {
			$args['show_not_going'] = $request->get_param( 'show_not_going' );
		}

		if ( $request->has_param( 'start_date' ) ) {
			$args['start_date'] = $request->get_param( 'start_date' );
		}

		if ( $request->has_param( 'end_date' ) ) {
			$args['end_date'] = $request->get_param( 'end_date' );
		}

		// Create the ticket.
		$ticket_obj = tribe( Ticket::class );
		$ticket_id  = $ticket_obj->create( $post_id, $args );

		if ( is_wp_error( $ticket_id ) ) {
			return $ticket_id;
		}

		$response = [
			'success'   => true,
			'ticket_id' => $ticket_id,
			'post_id'   => $post_id,
		];

		/**
		 * Filters the RSVP ticket creation response.
		 *
		 * @since TBD
		 *
		 * @param array           $response  The response data.
		 * @param int             $ticket_id The ticket ID.
		 * @param WP_REST_Request $request   The REST request.
		 */
		$response = apply_filters( 'tec_tickets_rsvp_v2_ticket_create_response', $response, $ticket_id, $request );

		return new WP_REST_Response( $response, 201 );
	}

	/**
	 * Handles the request that updates an RSVP ticket.
	 *
	 * @since TBD
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_Error|WP_REST_Response The response on success or a WP_Error instance on failure.
	 */
	public function update_item( WP_REST_Request $request ) {
		$ticket_id = $request->get_param( 'ticket_id' );

		// Verify this is an RSVP ticket.
		$ticket_obj = tribe( Ticket::class );
		if ( ! $ticket_obj->is_rsvp_ticket( $ticket_id ) ) {
			return new WP_Error(
				'tec_tickets_rsvp_v2_invalid_ticket',
				__( 'Invalid or non-RSVP ticket ID.', 'event-tickets' ),
				[ 'status' => 404 ]
			);
		}

		// Build update arguments from request.
		$args = [];

		if ( $request->has_param( 'name' ) ) {
			$args['name'] = $request->get_param( 'name' );
		}

		if ( $request->has_param( 'description' ) ) {
			$args['description'] = $request->get_param( 'description' );
		}

		if ( $request->has_param( 'capacity' ) ) {
			$args['capacity'] = $request->get_param( 'capacity' );
		}

		if ( $request->has_param( 'show_not_going' ) ) {
			$args['show_not_going'] = $request->get_param( 'show_not_going' );
		}

		if ( $request->has_param( 'start_date' ) ) {
			$args['start_date'] = $request->get_param( 'start_date' );
		}

		if ( $request->has_param( 'end_date' ) ) {
			$args['end_date'] = $request->get_param( 'end_date' );
		}

		if ( empty( $args ) ) {
			return new WP_Error(
				'tec_tickets_rsvp_v2_no_update_data',
				__( 'No update data provided.', 'event-tickets' ),
				[ 'status' => 400 ]
			);
		}

		// Update the ticket.
		$result = $ticket_obj->update( $ticket_id, $args );

		if ( ! $result ) {
			return new WP_Error(
				'tec_tickets_rsvp_v2_update_failed',
				__( 'Failed to update the RSVP ticket.', 'event-tickets' ),
				[ 'status' => 500 ]
			);
		}

		$response = [
			'success'   => true,
			'ticket_id' => $ticket_id,
		];

		/**
		 * Filters the RSVP ticket update response.
		 *
		 * @since TBD
		 *
		 * @param array           $response  The response data.
		 * @param int             $ticket_id The ticket ID.
		 * @param WP_REST_Request $request   The REST request.
		 */
		$response = apply_filters( 'tec_tickets_rsvp_v2_ticket_update_response', $response, $ticket_id, $request );

		return new WP_REST_Response( $response );
	}

	/**
	 * Handles the request that deletes an RSVP ticket.
	 *
	 * @since TBD
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_Error|WP_REST_Response The response on success or a WP_Error instance on failure.
	 */
	public function delete_item( WP_REST_Request $request ) {
		$ticket_id = $request->get_param( 'ticket_id' );

		// Verify this is an RSVP ticket.
		$ticket_obj = tribe( Ticket::class );
		if ( ! $ticket_obj->is_rsvp_ticket( $ticket_id ) ) {
			return new WP_Error(
				'tec_tickets_rsvp_v2_invalid_ticket',
				__( 'Invalid or non-RSVP ticket ID.', 'event-tickets' ),
				[ 'status' => 404 ]
			);
		}

		// Delete the ticket.
		$result = $ticket_obj->delete( $ticket_id );

		if ( ! $result ) {
			return new WP_Error(
				'tec_tickets_rsvp_v2_delete_failed',
				__( 'Failed to delete the RSVP ticket.', 'event-tickets' ),
				[ 'status' => 500 ]
			);
		}

		$response = [
			'success'   => true,
			'ticket_id' => $ticket_id,
			'deleted'   => true,
		];

		/**
		 * Filters the RSVP ticket delete response.
		 *
		 * @since TBD
		 *
		 * @param array           $response  The response data.
		 * @param int             $ticket_id The ticket ID.
		 * @param WP_REST_Request $request   The REST request.
		 */
		$response = apply_filters( 'tec_tickets_rsvp_v2_ticket_delete_response', $response, $ticket_id, $request );

		return new WP_REST_Response( $response );
	}
}
