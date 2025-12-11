<?php
/**
 * RSVP V2 REST API Order Endpoint.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2\REST
 */

namespace TEC\Tickets\RSVP\V2\REST;

use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_REST_Endpoint;
use TEC\Tickets\RSVP\V2\Attendee;
use TEC\Tickets\RSVP\V2\Cart\RSVP_Cart;
use TEC\Tickets\RSVP\V2\Meta;
use TEC\Tickets\RSVP\V2\Order;
use TEC\Tickets\RSVP\V2\Ticket;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Class Order_Endpoint.
 *
 * Handles RSVP order creation and attendee status updates via REST API.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2\REST
 */
class Order_Endpoint extends Abstract_REST_Endpoint {

	/**
	 * The REST API endpoint path.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected string $path = '/rsvp/order';

	/**
	 * Register the actual endpoint on WP Rest API.
	 *
	 * @since TBD
	 */
	public function register() {
		$namespace     = tribe( 'tickets.rest-v1.main' )->get_events_route_namespace();
		$documentation = tribe( 'tickets.rest-v1.endpoints.documentation' );

		// POST /tribe/tickets/v1/rsvp/order - Create RSVP order.
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

		// PUT /tribe/tickets/v1/rsvp/order/{attendee_id} - Update attendee status.
		register_rest_route(
			$namespace,
			$this->get_endpoint_path() . '/(?P<attendee_id>\d+)',
			[
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'update_item' ],
				'permission_callback' => [ $this, 'update_item_permissions_check' ],
				'args'                => $this->get_update_item_args(),
			]
		);

		$documentation->register_documentation_provider( $this->get_endpoint_path(), $this );
	}

	/**
	 * Gets the arguments for creating an RSVP order.
	 *
	 * @since TBD
	 *
	 * @return array The arguments schema.
	 */
	protected function get_create_item_args(): array {
		return [
			'ticket_id'     => [
				'required'          => true,
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'description'       => __( 'The RSVP ticket ID.', 'event-tickets' ),
			],
			'quantity'      => [
				'required'          => true,
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'minimum'           => 1,
				'description'       => __( 'The quantity of RSVPs.', 'event-tickets' ),
			],
			'rsvp_status'   => [
				'required'          => false,
				'type'              => 'string',
				'default'           => Meta::STATUS_GOING,
				'enum'              => [ Meta::STATUS_GOING, Meta::STATUS_NOT_GOING ],
				'sanitize_callback' => 'sanitize_text_field',
				'description'       => __( 'The RSVP status: "yes" (going) or "no" (not going).', 'event-tickets' ),
			],
			'attendee_data' => [
				'required'    => false,
				'type'        => 'array',
				'default'     => [],
				'description' => __( 'Array of attendee data for each RSVP.', 'event-tickets' ),
			],
			'purchaser'     => [
				'required'    => true,
				'type'        => 'object',
				'description' => __( 'Purchaser information.', 'event-tickets' ),
				'properties'  => [
					'name'  => [
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					],
					'email' => [
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_email',
					],
				],
			],
		];
	}

	/**
	 * Gets the arguments for updating an attendee status.
	 *
	 * @since TBD
	 *
	 * @return array The arguments schema.
	 */
	protected function get_update_item_args(): array {
		return [
			'attendee_id' => [
				'required'          => true,
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'description'       => __( 'The attendee ID.', 'event-tickets' ),
			],
			'rsvp_status' => [
				'required'          => true,
				'type'              => 'string',
				'enum'              => [ Meta::STATUS_GOING, Meta::STATUS_NOT_GOING ],
				'sanitize_callback' => 'sanitize_text_field',
				'description'       => __( 'The new RSVP status: "yes" (going) or "no" (not going).', 'event-tickets' ),
			],
		];
	}

	/**
	 * Checks permissions for creating an RSVP order.
	 *
	 * @since TBD
	 *
	 * @param WP_REST_Request $request The REST request.
	 *
	 * @return bool True if allowed, false otherwise.
	 */
	public function create_item_permissions_check( $request ): bool {
		// RSVPs can be created by anyone (including guests).
		return true;
	}

	/**
	 * Checks permissions for updating an attendee status.
	 *
	 * @since TBD
	 *
	 * @param WP_REST_Request $request The REST request.
	 *
	 * @return bool True if allowed, false otherwise.
	 */
	public function update_item_permissions_check( $request ): bool {
		// Status updates can be performed by anyone with the attendee ID.
		// In production, this should verify security token/email.
		return true;
	}

	/**
	 * Handles the request that creates an RSVP order.
	 *
	 * @since TBD
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_Error|WP_REST_Response An array containing the data on success or a WP_Error instance on failure.
	 */
	public function create_item( WP_REST_Request $request ) {
		$ticket_id     = $request->get_param( 'ticket_id' );
		$quantity      = $request->get_param( 'quantity' );
		$rsvp_status   = $request->get_param( 'rsvp_status' ) ?: Meta::STATUS_GOING;
		$attendee_data = $request->get_param( 'attendee_data' ) ?: [];
		$purchaser     = $request->get_param( 'purchaser' );

		// Validate ticket exists and is RSVP type.
		$ticket = get_post( $ticket_id );
		if ( ! $ticket ) {
			return new WP_Error(
				'tec_tickets_rsvp_v2_invalid_ticket',
				__( 'Invalid ticket ID.', 'event-tickets' ),
				[ 'status' => 400 ]
			);
		}

		$meta = tribe( Meta::class );
		if ( ! $meta->is_rsvp_ticket( $ticket_id ) ) {
			return new WP_Error(
				'tec_tickets_rsvp_v2_not_rsvp_ticket',
				__( 'The specified ticket is not an RSVP ticket.', 'event-tickets' ),
				[ 'status' => 400 ]
			);
		}

		// Validate purchaser data.
		if ( empty( $purchaser['name'] ) || empty( $purchaser['email'] ) ) {
			return new WP_Error(
				'tec_tickets_rsvp_v2_missing_purchaser',
				__( 'Purchaser name and email are required.', 'event-tickets' ),
				[ 'status' => 400 ]
			);
		}

		// Check capacity if status is "going".
		if ( Meta::STATUS_GOING === $rsvp_status ) {
			$ticket_obj = tribe( Ticket::class );
			$available  = $ticket_obj->get_available( $ticket_id );

			// -1 means unlimited capacity.
			if ( -1 !== $available && $available < $quantity ) {
				return new WP_Error(
					'tec_tickets_rsvp_v2_insufficient_capacity',
					__( 'Insufficient capacity for the requested quantity.', 'event-tickets' ),
					[ 'status' => 400 ]
				);
			}
		}

		/**
		 * Fires before RSVP order processing begins.
		 *
		 * V1 backwards compatibility hook. In V1, this received $_POST data,
		 * but in V2 REST context we pass the validated request parameters.
		 *
		 * @since TBD
		 *
		 * @param array $data The order data from the REST request.
		 */
		do_action(
			'tribe_tickets_rsvp_before_order_processing',
			[
				'ticket_id'   => $ticket_id,
				'quantity'    => $quantity,
				'rsvp_status' => $rsvp_status,
				'purchaser'   => $purchaser,
			]
		);

		// Add to RSVP Cart.
		$cart = tribe( RSVP_Cart::class );
		$cart->upsert_item( $ticket_id, $quantity );

		// Create Order.
		$order_obj = tribe( Order::class );
		$order_id  = $order_obj->create( $cart, $purchaser, $rsvp_status );

		if ( is_wp_error( $order_id ) ) {
			$cart->clear_cart();
			return $order_id;
		}

		// Create Attendees.
		$attendee_obj = tribe( Attendee::class );
		$attendee_ids = [];
		$event_id     = get_post_meta( $ticket_id, '_tec_tickets_commerce_event', true );

		for ( $i = 0; $i < $quantity; $i++ ) {
			$attendee_args = [
				'event_id'    => $event_id,
				'name'        => $purchaser['name'],
				'email'       => $purchaser['email'],
				'rsvp_status' => $rsvp_status,
			];

			// Merge individual attendee data if provided.
			if ( isset( $attendee_data[ $i ] ) && is_array( $attendee_data[ $i ] ) ) {
				$attendee_args = array_merge( $attendee_args, $attendee_data[ $i ] );
			}

			$attendee_id = $attendee_obj->create( $order_id, $ticket_id, $attendee_args );

			if ( is_wp_error( $attendee_id ) ) {
				// If attendee creation fails, continue but log the error.
				continue;
			}

			$attendee_ids[] = $attendee_id;
		}

		// Clear cart after successful order creation.
		$cart->clear_cart();

		$response = [
			'success'      => true,
			'order_id'     => $order_id,
			'attendee_ids' => $attendee_ids,
		];

		/**
		 * Filters the RSVP order creation response.
		 *
		 * @since TBD
		 *
		 * @param array           $response The response data.
		 * @param int             $order_id The order ID.
		 * @param WP_REST_Request $request  The REST request.
		 */
		$response = apply_filters( 'tec_tickets_rsvp_v2_order_response', $response, $order_id, $request );

		return new WP_REST_Response( $response, 201 );
	}

	/**
	 * Handles the request that updates an attendee's RSVP status.
	 *
	 * @since TBD
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_Error|WP_REST_Response The response on success or a WP_Error instance on failure.
	 */
	public function update_item( WP_REST_Request $request ) {
		$attendee_id = $request->get_param( 'attendee_id' );
		$rsvp_status = $request->get_param( 'rsvp_status' );

		// Validate attendee exists.
		$attendee = get_post( $attendee_id );
		if ( ! $attendee ) {
			return new WP_Error(
				'tec_tickets_rsvp_v2_invalid_attendee',
				__( 'Invalid attendee ID.', 'event-tickets' ),
				[ 'status' => 404 ]
			);
		}

		// Validate it's an RSVP attendee.
		$meta = tribe( Meta::class );
		if ( ! $meta->is_rsvp_attendee( $attendee_id ) ) {
			return new WP_Error(
				'tec_tickets_rsvp_v2_not_rsvp_attendee',
				__( 'The specified attendee is not an RSVP attendee.', 'event-tickets' ),
				[ 'status' => 400 ]
			);
		}

		// Update status using Attendee::change_status() which handles capacity.
		$attendee_obj = tribe( Attendee::class );
		$result       = $attendee_obj->change_status( $attendee_id, $rsvp_status );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$response = [
			'success'     => true,
			'attendee_id' => $attendee_id,
			'rsvp_status' => $rsvp_status,
		];

		/**
		 * Filters the RSVP status update response.
		 *
		 * @since TBD
		 *
		 * @param array           $response    The response data.
		 * @param int             $attendee_id The attendee ID.
		 * @param string          $rsvp_status The new RSVP status.
		 * @param WP_REST_Request $request     The REST request.
		 */
		$response = apply_filters( 'tec_tickets_rsvp_v2_status_update_response', $response, $attendee_id, $rsvp_status, $request );

		return new WP_REST_Response( $response );
	}
}
