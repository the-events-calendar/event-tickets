<?php
/**
 * RSVP V2: Ticket Meta Endpoint.
 *
 * Handles IAC (Individual Attendee Collection) and attendee meta field updates.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2\REST
 */

namespace TEC\Tickets\RSVP\V2\REST;

use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_REST_Endpoint;
use TEC\Tickets\Event;
use Tribe__Utils__Array as Arr;
use WP_Post;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Class Ticket_Meta_Endpoint.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2\REST
 */
class Ticket_Meta_Endpoint extends Abstract_REST_Endpoint {

	/**
	 * The REST API endpoint path.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected string $path = '/rsvp/v2/ticket/meta';

	/**
	 * Register the actual endpoint on WP Rest API.
	 *
	 * @since TBD
	 */
	public function register(): void {
		$namespace = tribe( 'tickets.rest-v1.main' )->get_events_route_namespace();

		register_rest_route(
			$namespace,
			$this->get_endpoint_path(),
			[
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'handle_update_ticket_meta' ],
				'permission_callback' => [ $this, 'check_permission' ],
			]
		);
	}

	/**
	 * Checks if the current user has the capability to edit the post and verifies the nonce.
	 *
	 * @since TBD
	 *
	 * @param WP_REST_Request $request The current REST request.
	 *
	 * @return bool True if the user has the edit post capability and nonce is valid, false otherwise.
	 */
	public function check_permission( WP_REST_Request $request ): bool {
		$nonce = $request->get_param( '_wpnonce' );

		$nonce_value = '';
		if ( is_array( $nonce ) && isset( $nonce['_wpnonce'] ) ) {
			$nonce_value = $nonce['_wpnonce'];
		}

		if ( is_string( $nonce ) ) {
			$nonce_value = $nonce;
		}

		if ( empty( $nonce_value ) ) {
			$nonce_value = $request->get_header( 'X-WP-Nonce' );
		}

		if ( ! wp_verify_nonce( $nonce_value, 'wp_rest' ) ) {
			return false;
		}

		$post_id = $request->get_param( 'post_ID' );

		if ( empty( $post_id ) ) {
			return current_user_can( 'edit_posts' );
		}

		return current_user_can( 'edit_post', $post_id );
	}

	/**
	 * Handles the request to update IAC and attendee meta fields for existing tickets.
	 *
	 * @since TBD
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_REST_Response The response.
	 */
	public function handle_update_ticket_meta( WP_REST_Request $request ): WP_REST_Response {
		$request_params = $request->get_params();
		$post_id        = Arr::get( $request_params, 'post_ID' );
		$ticket_id      = Arr::get( $request_params, 'ticket_id', '' );

		if ( empty( $post_id ) || empty( $ticket_id ) ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => __( 'Missing required post ID or ticket ID.', 'event-tickets' ),
				],
				400
			);
		}

		$post_id   = Event::filter_event_id( $post_id );
		$ticket_id = absint( $ticket_id );

		$ticket_post = get_post( $ticket_id );
		if ( ! $ticket_post instanceof WP_Post ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => __( 'Ticket not found.', 'event-tickets' ),
				],
				404
			);
		}

		/**
		 * Allow for processing additional IAC and attendee meta fields before updating.
		 *
		 * @since TBD
		 *
		 * @param array $request_params The original request parameters.
		 * @param int   $ticket_id      The ticket ID being updated.
		 * @param int   $post_id        The post ID.
		 */
		$request_params = apply_filters( 'tec_tickets_rsvp_process_additional_fields', $request_params, $ticket_id, $post_id );

		/**
		 * Allow for additional processing after IAC and attendee meta updates.
		 *
		 * @since TBD
		 *
		 * @param int   $ticket_id      The ticket ID being updated.
		 * @param int   $post_id        The post ID.
		 * @param array $request_params The original request parameters.
		 */
		do_action( 'tec_tickets_rsvp_after_meta_update', $ticket_id, $post_id, $request_params );

		return new WP_REST_Response(
			[
				'success'   => true,
				'ticket_id' => $ticket_id,
			]
		);
	}
}
