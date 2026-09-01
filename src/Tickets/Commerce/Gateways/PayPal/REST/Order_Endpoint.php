<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal\REST;

use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_REST_Endpoint;
use TEC\Tickets\Commerce\Gateways\PayPal\Gateway;
use TEC\Tickets\Commerce\Gateways\PayPal\Status;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Stock_Validator;

use TEC\Tickets\Commerce\Gateways\PayPal\Client;
use TEC\Tickets\Commerce\Status\Denied;
use TEC\Tickets\Commerce\Status\Not_Completed;
use TEC\Tickets\Commerce\Status\Pending;
use TEC\Tickets\Commerce\Status\Status_Handler;
use TEC\Tickets\Commerce\Status\Status_Interface;
use TEC\Tickets\Commerce\Status\Voided;
use TEC\Tickets\Commerce\Success;
use TEC\Tickets\Commerce\Values\Legacy_Value_Factory;
use Tribe__Tickets__Tickets as Tickets;
use Tribe__Utils__Array as Arr;

use WP_Error;
use WP_Post;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;


/**
 * Class Order Endpoint.
 *
 * @since 5.1.9
 *
 * @package TEC\Tickets\Commerce\Gateways\PayPal\REST
 */
class Order_Endpoint extends Abstract_REST_Endpoint {

	/**
	 * The REST API endpoint path.
	 *
	 * @since 5.1.9
	 *
	 * @var string
	 */
	protected string $path = '/commerce/paypal/order';

	/**
	 * Register the actual endpoint on WP Rest API.
	 *
	 * @since 5.1.9
	 */
	public function register() {
		$namespace     = tribe( 'tickets.rest-v1.main' )->get_events_route_namespace();
		$documentation = tribe( 'tickets.rest-v1.endpoints.documentation' );

		register_rest_route(
			$namespace,
			$this->get_endpoint_path(),
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'args'                => $this->create_order_args(),
				'callback'            => [ $this, 'handle_create_order' ],
				'permission_callback' => '__return_true',
			]
		);

		register_rest_route(
			$namespace,
			$this->get_endpoint_path() . '/(?P<order_id>[0-9a-zA-Z]+)',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'args'                => $this->update_order_args(),
				'callback'            => [ $this, 'handle_update_order' ],
				'permission_callback' => [ $this, 'current_user_can_edit_order' ],
			]
		);

		register_rest_route(
			$namespace,
			$this->get_endpoint_path() . '/(?P<order_id>[0-9a-zA-Z]+)',
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'args'                => $this->fail_order_args(),
				'callback'            => [ $this, 'handle_fail_order' ],
				'permission_callback' => [ $this, 'current_user_can_edit_order' ],
			]
		);

		$documentation->register_documentation_provider( $this->get_endpoint_path(), $this );
	}

	/**
	 * Handles the request that creates an order with Tickets Commerce and the PayPal gateway.
	 *
	 * @since 5.1.9
	 * @since 5.6.4 Include Event/Post title in the Ticket name.
	 * @since 5.27.6.1 Removed order data from response for failed orders.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_Error|WP_REST_Response An array containing the data on success or a WP_Error instance on failure.
	 */
	public function handle_create_order( WP_REST_Request $request ) {
		$response = [
			'success' => false,
		];

		$messages = $this->get_error_messages();
		$data = $request->get_json_params();
		$purchaser = tribe( Order::class )->get_purchaser_data( $data );

		if ( is_wp_error( $purchaser ) ) {
			return $purchaser;
		}

		// Validate stock availability with database locking before creating order.
		$cart             = tribe( Cart::class );
		$stock_validation = tribe( Stock_Validator::class )->validate_cart_stock_with_lock( $cart );

		if ( is_wp_error( $stock_validation ) ) {
			return $stock_validation;
		}

		$order = tribe( Order::class )->create_from_cart( tribe( Gateway::class ), $purchaser );

		if ( ! $order ) {
			return new WP_Error( 'tec-tc-gateway-paypal-failed-creating-order', $messages['failed-creating-order'] );
		}

		$unit = [
			'reference_id' => $order->ID,
			'value'        => (string) Legacy_Value_Factory::to_precision_value( $order->total_value ),
			'currency'     => $order->currency,
			'first_name'   => $order->purchaser['first_name'],
			'last_name'    => $order->purchaser['last_name'],
			'email'        => $order->purchaser['email'],
		];

		foreach ( $order->items as $item ) {
			$unit['items'][] = $this->get_unit_data( $item, $order );
		}

		/**
		 * Filters the unit data for the order before sending it to PayPal.
		 *
		 * @since 5.21.0
		 *
		 * @param array          $unit     The structured data for the order.
		 * @param WP_Post        $order    The current order object.
		 * @param Order_Endpoint $endpoint The current instance of the Order_Endpoint class.
		 */
		$unit = (array) apply_filters( 'tec_tickets_commerce_paypal_order_unit', $unit, $order, $this );

		$paypal_order = tribe( Client::class )->create_order( $unit );

		if ( empty( $paypal_order['id'] ) || empty( $paypal_order['create_time'] ) ) {
			return new WP_Error( 'tec-tc-gateway-paypal-failed-creating-order', $messages['failed-creating-order'] );
		}

		$debug_header = tribe( Client::class )->get_debug_header();
		if ( ! empty( $debug_header ) ) {
			$paypal_order['debug_id'] = $debug_header;
		}

		$updated = tribe( Order::class )->modify_status( $order->ID, Pending::SLUG, [
			'gateway_payload'  => $paypal_order,
			'gateway_order_id' => $paypal_order['id'],
		] );

		if ( is_wp_error( $updated ) ) {
			return $updated;
		}

		// Respond with the ID for Paypal Usage.
		$response['success'] = true;
		$response['id']      = $paypal_order['id'];

		$this->pending_order->set( $response['id'] );

		return new WP_REST_Response( $response );
	}

	/**
	 * Retrieves the unit data for an item in the cart.
	 *
	 * By default, the item type will be considered a 'ticket' if not specified.
	 * This method handles different item types with a switch case, providing custom logic
	 * for 'ticket' and a default behavior for other item types.
	 * An overarching filter allows for customization of the final returned data.
	 *
	 * @since 5.18.0
	 *
	 * @param array   $item The cart item for which to retrieve unit data.
	 * @param WP_Post $order The order from the items in the cart.
	 *
	 * @return array The structured data for the item, including 'name', 'unit_amount', 'quantity', 'item_total', and
	 *     'sku'.
	 */
	public function get_unit_data( array $item, WP_Post $order ) {
		if ( ! $order->ID ) {
			return [];
		}

		$type = $item['type'] ?? 'ticket';

		switch ( $type ) {
			case 'ticket':
				$unit_data = $this->get_unit_data_for_ticket( $item, $order );
				break;

			default:
				/**
				 * Filters the unit data for custom item types in the cart.
				 *
				 * This filter allows external developers to generate and customize the unit data
				 * for items in the cart based on the item type (other than 'ticket').
				 *
				 * The filter name is dynamic and uses the item type (`$type`) to provide flexibility for
				 * different item categories.
				 *
				 * Example: If `$type` is 'fee', the filter will be `tec_commerce_get_unit_data_fee`.
				 *
				 * @since 5.18.0
				 *
				 * @param array   $item   The cart item for which the unit data is being generated.
				 * @param WP_Post $order  The current order object.
				 *
				 * @return array The unit data for the item.
				 */
				$unit_data = apply_filters( "tec_tickets_commerce_paypal_order_get_unit_data_{$type}", $item, $order );
				break;
		}

		/**
		 * Filters the unit data for an item in REST context.
		 *
		 * @since 5.18.0
		 *
		 * @param array   $unit_data The structured data for the item.
		 * @param array   $item      The order item for which the unit data is being generated.
		 * @param WP_Post $order     The current order object.
		 */
		return apply_filters( 'tec_tickets_commerce_paypal_order_get_unit_data', $unit_data, $item, $order );
	}

	/**
	 * Retrieves the default unit data for a ticket in the cart.
	 *
	 * This method is used when the item type is 'ticket', and it structures the data
	 * for a ticket item, including details such as name, price, quantity, and SKU.
	 *
	 * @since 5.18.0
	 *
	 * @param array   $item The cart item (representing the ticket).
	 * @param WP_Post $order The order from the items in the cart.
	 *
	 * @return array<string,mixed> The structured data for the ticket item.
	 */
	protected function get_unit_data_for_ticket( array $item, WP_Post $order ) {
		if ( ! $order->ID ) {
			return [];
		}

		// Default ticket logic.
		$ticket     = Tickets::load_ticket_object( $item['ticket_id'] );
		$post_title = get_the_title( $item['event_id'] );
		$item_name  = "{$ticket->name} - {$post_title}";

		return [
			'name'        => $this->format_order_item_name( $item_name ),
			'unit_amount' => [
				'value'         => (string) $item['price'],
				'currency_code' => $order->currency,
			],
			'quantity'    => (string) $item['quantity'],
			'item_total'  => [
				'value'         => (string) $item['sub_total'],
				'currency_code' => $order->currency,
			],
			'sku'         => $ticket->sku,
		];
	}

	/**
	 * Handles the request that updates an order with Tickets Commerce and the PayPal gateway.
	 *
	 * @since 5.1.9
	 * @since 5.27.6.1 Removed order data from response for failed orders.
	 * @since TBD Records what PayPal answered and fails when it did not acknowledge a capture.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_Error|WP_REST_Response An array containing the data on success or a WP_Error instance on failure.
	 */
	public function handle_update_order( WP_REST_Request $request ) {
		$response = [
			'success' => false,
		];

		$messages = $this->get_error_messages();

		$paypal_order_id = $request->get_param( 'order_id' );

		$order = tec_tc_orders()->by_args(
			[
				'status'           => 'any',
				'gateway_order_id' => $paypal_order_id,
			]
		)->first();

		if ( ! $order ) {
			return new WP_Error(
				'tec-tc-gateway-paypal-nonexistent-order-id',
				$messages['nonexistent-order-id'],
				[ 'status' => 404 ]
			);
		}

		$recheck = $request->get_param( 'recheck' );

		if ( $recheck ) {
			return $this->handle_recheck_order( $paypal_order_id, $order );
		}

		// An earlier request already resolved this order, and capturing twice is an error at PayPal.
		// Report the status the order actually carries rather than asking the buyer to pay again.
		if ( ! $this->order_is_in_flight( $order ) ) {
			$settled = tribe( Status_Handler::class )->get_by_wp_slug( (string) get_post_status( $order->ID ) );

			if ( ! $settled ) {
				return new WP_Error(
					'tec-tc-gateway-paypal-invalid-capture-status',
					$messages['invalid-capture-status'],
					[ 'status' => 500 ]
				);
			}

			if ( ! $this->status_is_paid( $settled ) ) {
				return new WP_Error(
					'tec-tc-gateway-paypal-capture-declined',
					$messages['capture-declined'],
					[ 'status' => 402 ]
				);
			}

			return $this->settled_response( $order, $paypal_order_id, $settled );
		}

		$payer_id = $request->get_param( 'payer_id' );

		$paypal_capture_response = tribe( Client::class )->capture_order( $paypal_order_id, $payer_id );

		// The capture got back nothing we can read: a transport failure, the five second default
		// timeout, or a body PayPal did not fill in. PayPal may still have taken the money, so this is
		// an unknown outcome and not a failed one. The recheck reads the order back and settles it
		// either way, and captures it when nothing did. Ending the payment here instead tells the
		// buyer to try again for a charge that may already have landed, and when the second order
		// completes, end_duplicated_pending_orders archives the one that was actually paid.
		if ( ! $this->is_paypal_payload( $paypal_capture_response ) ) {
			$response['success']  = true;
			$response['order_id'] = $paypal_order_id;

			return new WP_REST_Response( $response );
		}

		$debug_header = tribe( Client::class )->get_debug_header();
		if ( ! empty( $debug_header ) ) {
			$paypal_capture_response['debug_id'] = $debug_header;
		}

		if ( 'UNPROCESSABLE_ENTITY' === Arr::get( $paypal_capture_response, 'name' ) ) {
			// Flag the order as Denied.
			tribe( Order::class )->modify_status( $order->ID, Denied::SLUG, [
				'gateway_payload' => $paypal_capture_response,
			] );

			return new WP_Error(
				'tec-tc-gateway-paypal-failed-capture',
				$messages['failed-capture'],
				[
					'status'  => 400,
					'name'    => Arr::get( $paypal_capture_response, 'name' ),
					'details' => (array) Arr::get( $paypal_capture_response, 'details', [] ),
				]
			);
		}

		// PayPal answered with an error object rather than an order. RESOURCE_NOT_FOUND,
		// PERMISSION_DENIED and the rest land here, and none of them mean the payment is still in
		// flight. Without this they read as unsettled and the buyer is sent into a recheck that has
		// nothing to find.
		if ( ! isset( $paypal_capture_response['status'] ) && ! empty( $paypal_capture_response['name'] ) ) {
			return new WP_Error(
				'tec-tc-gateway-paypal-failed-capture',
				$messages['failed-capture'],
				[
					'status'  => 400,
					'name'    => Arr::get( $paypal_capture_response, 'name' ),
					'details' => (array) Arr::get( $paypal_capture_response, 'details', [] ),
				]
			);
		}

		// Record the capture before answering. The browser may never receive this response, and until
		// the capture is on the order nothing on the site knows the money was taken.
		$status = $this->settle_order_status( $order, $paypal_capture_response );

		if ( is_wp_error( $status ) ) {
			return $status;
		}

		if ( null === $status ) {
			// PayPal took the money but has not settled the order, which an authentication such as
			// BankID finishes on its own side. The recheck is what waits for that.
			$response['success']  = true;
			$response['order_id'] = $paypal_order_id;

			return new WP_REST_Response( $response );
		}

		// The capture answered, so the buyer has somewhere to go without a second round trip. A
		// blocked or delayed recheck can no longer be what strands them.
		return $this->settled_response( $order, $paypal_order_id, $status );
	}

	/**
	 * Gets the Order object again, in another request, to check for purchases possibly denied after creation.
	 *
	 * @since 5.4.0.2
	 * @since 5.27.6.1 Removed order data from response for failed orders.
	 * @since TBD Captures an approved order nothing captured yet, and stops writing unsettled PayPal
	 *        states over the order.
	 *
	 * @param string  $order_id The PayPal order ID.
	 * @param WP_Post $order    The TC Order object.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function handle_recheck_order( $order_id, $order ) {
		$messages = $this->get_error_messages();

		$paypal_order_response = tribe( Client::class )->get_order( $order_id );

		// Neither request reached PayPal, so whether the money moved is still unknown. This is the one
		// error the buyer must not read as "try again": that is how an unconfirmed capture turns into
		// a second charge.
		if ( ! $this->is_paypal_payload( $paypal_order_response ) ) {
			return new WP_Error(
				'tec-tc-gateway-paypal-unconfirmed-capture',
				$messages['unconfirmed-capture'],
				[ 'status' => 502 ]
			);
		}

		// The payer approved and nothing was captured, so either the capture request never reached
		// PayPal or its answer never got back. Capturing here is what keeps a delayed or blocked round
		// trip from leaving an approved order unpaid.
		if (
			Status::APPROVED === Arr::get( $paypal_order_response, 'status' )
			&& null === $this->get_deciding_capture( $paypal_order_response )
		) {
			$paypal_capture_response = tribe( Client::class )->capture_order( $order_id );

			if ( $this->is_paypal_payload( $paypal_capture_response ) ) {
				$paypal_order_response = $paypal_capture_response;
			}
		}

		$status = $this->settle_order_status( $order, $paypal_order_response );

		if ( is_wp_error( $status ) ) {
			return $status;
		}

		// PayPal has taken no decision we can record. There is no webhook for this gateway, so nothing
		// will finish the order later: sending the buyer to "Order Received" would promise a receipt
		// and tickets that never arrive, and clearing the cart would remove their only way back. An
		// error they can act on beats a success page that is not true.
		if ( null === $status ) {
			return new WP_Error(
				'tec-tc-gateway-paypal-unconfirmed-capture',
				$messages['unconfirmed-capture'],
				[ 'status' => 502 ]
			);
		}

		return $this->settled_response( $order, $order_id, $status );
	}

	/**
	 * Writes the state PayPal settled on onto the Tickets Commerce order.
	 *
	 * @since TBD
	 *
	 * @param WP_Post $order    The Tickets Commerce order.
	 * @param array   $response A PayPal order or capture response.
	 *
	 * @return Status_Interface|WP_Error|null The status written, an error the buyer has to be told
	 *                                        about, or null while PayPal has settled nothing.
	 */
	protected function settle_order_status( $order, array $response ) {
		$messages      = $this->get_error_messages();
		$paypal_status = $this->get_settled_status( $response );

		if ( null === $paypal_status ) {
			return null;
		}

		$status = tribe( Status::class )->convert_to_commerce_status( $paypal_status );

		if ( ! $status ) {
			return new WP_Error(
				'tec-tc-gateway-paypal-invalid-capture-status',
				$messages['invalid-capture-status'],
				[ 'status' => 500 ]
			);
		}

		$updated = tribe( Order::class )->modify_status(
			$order->ID,
			$status->get_slug(),
			[ 'gateway_payload' => $response ]
		);

		// modify_status refuses a transition to the status the order already carries, so a recheck of
		// an order an earlier request settled reports a failure that did not happen. Only a status
		// that never made it onto the order is an unconfirmed capture.
		if ( ! $updated && $status->get_wp_slug() !== get_post_status( $order->ID ) ) {
			return new WP_Error(
				'tec-tc-gateway-paypal-unconfirmed-capture',
				$messages['unconfirmed-capture'],
				[ 'status' => 502 ]
			);
		}

		if ( ! $this->status_is_paid( $status ) ) {
			return new WP_Error(
				'tec-tc-gateway-paypal-capture-declined',
				$messages['capture-declined'],
				[ 'status' => 402 ]
			);
		}

		return $status;
	}

	/**
	 * Whether a status means the buyer paid and has tickets waiting for them.
	 *
	 * Only a paid order may be answered with the success page. Denied, Voided and anything else a
	 * settled PayPal response maps to took no money and generated no attendees, so sending the buyer
	 * there would promise a receipt that does not exist.
	 *
	 * @since TBD
	 *
	 * @param Status_Interface $status The status the order settled on.
	 *
	 * @return bool
	 */
	protected function status_is_paid( Status_Interface $status ): bool {
		return $status->has_flags( [ 'complete' ] );
	}

	/**
	 * Builds the response that takes the buyer off checkout and on to their order.
	 *
	 * @since TBD
	 *
	 * @param WP_Post          $order           The Tickets Commerce order.
	 * @param string           $paypal_order_id The PayPal order ID.
	 * @param Status_Interface $status          The status PayPal settled on.
	 *
	 * @return WP_REST_Response
	 */
	protected function settled_response( $order, string $paypal_order_id, Status_Interface $status ): WP_REST_Response {
		// When we have success we clear the cart.
		tribe( Cart::class )->clear_cart();

		return new WP_REST_Response(
			[
				'success'      => true,
				'status'       => $status->get_slug(),
				'order_id'     => $order->ID,
				'redirect_url' => add_query_arg( [ 'tc-order-id' => $paypal_order_id ], tribe( Success::class )->get_url() ),
			]
		);
	}

	/**
	 * Resolves the status a PayPal response has settled on, if it has settled on one.
	 *
	 * The order status on its own is not an answer: an order reads COMPLETED as soon as a capture
	 * exists, whatever that capture's own status is, and reads CREATED or APPROVED while the payer
	 * finishes an authentication such as BankID. The capture, when there is one, is the authority.
	 *
	 * @since TBD
	 *
	 * @param array $response A PayPal order or capture response.
	 *
	 * @return string|null The settled PayPal status, or null while the payment is still in flight.
	 */
	protected function get_settled_status( array $response ): ?string {
		$capture = $this->get_deciding_capture( $response );
		$status  = null !== $capture ? Arr::get( $capture, 'status' ) : Arr::get( $response, 'status' );

		if ( ! is_string( $status ) || in_array( $status, $this->get_in_flight_statuses(), true ) ) {
			return null;
		}

		return $status;
	}

	/**
	 * Picks the capture that decides a PayPal order out of its purchase units.
	 *
	 * @since TBD
	 *
	 * @param array $response A PayPal order or capture response.
	 *
	 * @return array|null The deciding capture, or null when nothing has been captured.
	 */
	protected function get_deciding_capture( array $response ): ?array {
		$captures = [];

		foreach ( (array) Arr::get( $response, 'purchase_units', [] ) as $unit ) {
			foreach ( (array) Arr::get( $unit, [ 'payments', 'captures' ], [] ) as $capture ) {
				if ( ! empty( $capture['status'] ) ) {
					$captures[] = $capture;
				}
			}
		}

		if ( empty( $captures ) ) {
			return null;
		}

		usort(
			$captures,
			static function ( $a, $b ) {
				return strtotime( Arr::get( $a, 'update_time', '' ) ) <=> strtotime( Arr::get( $b, 'update_time', '' ) );
			}
		);

		foreach ( $captures as $capture ) {
			if ( ! empty( $capture['final_capture'] ) ) {
				return $capture;
			}
		}

		return end( $captures );
	}

	/**
	 * PayPal statuses that mean the payment has not resolved yet.
	 *
	 * None of these may be written over the order. They map to Tickets Commerce statuses that generate
	 * attendees or walk the order backwards out of pending, and reaching one of them means the money
	 * has not been taken.
	 *
	 * @since TBD
	 *
	 * @return string[]
	 */
	protected function get_in_flight_statuses(): array {
		return [
			Status::CREATED,
			Status::SAVED,
			Status::APPROVED,
			Status::PAYER_ACTION_REQUIRED,
			Status::PENDING,
		];
	}

	/**
	 * Whether a Client response is a payload PayPal produced.
	 *
	 * A transport failure comes back as a WP_Error and a body the Client could not decode comes back
	 * as the raw wp_remote_* response. Neither says anything about the payment, and treating either as
	 * an answer is how a blocked request ends up reported as a successful capture.
	 *
	 * @since TBD
	 *
	 * @param mixed $response A Client response.
	 *
	 * @return bool
	 */
	protected function is_paypal_payload( $response ): bool {
		if ( is_wp_error( $response ) || ! is_array( $response ) ) {
			return false;
		}

		return ! isset( $response['response'], $response['headers'] );
	}

	/**
	 * Handles the request that handles failing an order with Tickets Commerce and the PayPal gateway.
	 *
	 * @since 5.2.0
	 * @since 5.27.6.1 Removed order data from response for failed orders.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_Error|WP_REST_Response An array containing the data on success or a WP_Error instance on failure.
	 */
	public function handle_fail_order( WP_REST_Request $request ) {
		$response = [
			'success' => false,
		];

		$paypal_order_id = $request->get_param( 'order_id' );
		$order           = tec_tc_orders()->by_args( [
			'status'           => 'any',
			'gateway_order_id' => $paypal_order_id,
		] )->first();

		$messages = $this->get_error_messages();

		if ( ! $order ) {
			return new WP_Error( 'tec-tc-gateway-paypal-nonexistent-order-id', null );
		}

		$failed_status = $request->get_param( 'failed_status' );
		if ( empty( $failed_status ) ) {
			$failed_status = 'not-completed';
		}

		$allowed_failure_statuses = [ Not_Completed::SLUG, Denied::SLUG, Voided::SLUG ];

		if ( ! in_array( $failed_status, $allowed_failure_statuses, true ) ) {
			return new WP_Error( 'tec-tc-gateway-paypal-invalid-failed-status', null );
		}

		$status = tribe( Status_Handler::class )->get_by_slug( $failed_status );

		if ( ! $status ) {
			return new WP_Error( 'tec-tc-gateway-paypal-invalid-failed-status', null );
		}

		/**
		 * @todo possible determine if we should have error code associated with the failing of this order.
		 */
		$updated = tribe( Order::class )->modify_status( $order->ID, $status->get_slug() );

		if ( is_wp_error( $updated ) ) {
			return $updated;
		}

		$response['success']  = true;
		$response['status']   = $status->get_slug();
		$response['order_id'] = $order->ID;
		$response['title']    = $messages['canceled-creating-order'];

		return new WP_REST_Response( $response );
	}

	/**
	 * Arguments used for the signup redirect.
	 *
	 * @since 5.1.9
	 *
	 * @return array
	 */
	public function create_order_args() {
		return [];
	}

	/**
	 * Arguments used for the updating order for PayPal.
	 *
	 * @since 5.1.9
	 *
	 * @return array
	 */
	public function update_order_args() {
		return [
			'order_id' => [
				'description'       => __( 'Order ID in PayPal', 'event-tickets' ),
				'required'          => true,
				'type'              => 'string',
				'validate_callback' => static function ( $value ) {
					if ( ! is_string( $value ) ) {
						return new WP_Error( 'rest_invalid_param', 'The order ID argument must be a string.', [ 'status' => 400 ] );
					}

					return $value;
				},
				'sanitize_callback' => [ $this, 'sanitize_callback' ],
			],
			'payer_id' => [
				'description'       => __( 'Payer ID token from PayPal', 'event-tickets' ),
				'required'          => false,
				'type'              => 'string',
				'validate_callback' => static function ( $value ) {
					if ( ! is_string( $value ) ) {
						return new WP_Error( 'rest_invalid_param', 'The payer ID argument must be a string.', [ 'status' => 400 ] );
					}

					return $value;
				},
				'sanitize_callback' => [ $this, 'sanitize_callback' ],
			],
		];
	}

	/**
	 * Arguments used for the deleting order for PayPal.
	 *
	 * @since 5.2.0
	 * @since 5.27.6.1 Removed order data from response for failed orders.
	 *
	 * @return array
	 */
	public function fail_order_args() {
		return [
			'order_id'      => [
				'description'       => __( 'Order ID in PayPal', 'event-tickets' ),
				'required'          => true,
				'type'              => 'string',
				'validate_callback' => static function ( $value ) {
					if ( ! is_string( $value ) ) {
						return new WP_Error( 'rest_invalid_param', 'The order ID argument must be a string.', [ 'status' => 400 ] );
					}

					return $value;
				},
				'sanitize_callback' => [ $this, 'sanitize_callback' ],
			],
			'failed_status' => [
				'description'       => __( 'To which status the failing should change this order to', 'event-tickets' ),
				'required'          => false,
				'type'              => 'string',
				'validate_callback' => static function ( $value ) {
					$allowed = [ Not_Completed::SLUG, Denied::SLUG, Voided::SLUG ];
					if ( ! is_string( $value ) || ! in_array( $value, $allowed, true ) ) {
						return new WP_Error( 'rest_invalid_param', 'The failed status argument must be a valid failure status.', [ 'status' => 400 ] );
					}

					return $value;
				},
				'sanitize_callback' => [ $this, 'sanitize_callback' ],
			],
			'failed_reason' => [
				'description'       => __( 'Why this particular order has failed.', 'event-tickets' ),
				'required'          => false,
				'type'              => 'string',
				'validate_callback' => static function ( $value ) {
					if ( ! is_string( $value ) ) {
						return new WP_Error( 'rest_invalid_param', 'The failed reason argument must be a string.', [ 'status' => 400 ] );
					}

					return $value;
				},
				'sanitize_callback' => [ $this, 'sanitize_callback' ],
			],
		];
	}

	/**
	 * Sanitize a request argument based on details registered to the route.
	 *
	 * @since 5.1.9
	 *
	 * @param mixed $value Value of the 'filter' argument.
	 *
	 * @return string|array
	 */
	public function sanitize_callback( $value ) {
		if ( is_array( $value ) ) {
			return array_map( 'sanitize_text_field', $value );
		}

		return sanitize_text_field( $value );
	}

	/**
	 * Returns an array of error messages that are used by the API responses.
	 *
	 * @since 5.2.0
	 *
	 * @return array $messages Array of error messages.
	 */
	public function get_error_messages() {
		$messages = [
			'failed-creating-order'   => __( 'Creating new PayPal order failed. Please try again.', 'event-tickets' ),
			'canceled-creating-order' => __( 'Your PayPal order was cancelled.', 'event-tickets' ),
			'nonexistent-order-id'    => __( 'Provided Order id is not valid.', 'event-tickets' ),
			'failed-capture'          => __( 'There was a problem while processing your payment, please try again.', 'event-tickets' ),
			'capture-declined'        => __( 'Your payment was declined.', 'event-tickets' ),
			'unconfirmed-capture'     => __( 'We could not confirm your payment. Check your PayPal account before trying again, so that you are not charged twice.', 'event-tickets' ),
			'invalid-capture-status'  => __( 'There was a problem with the Order status change, please try again.', 'event-tickets' ),
		];

		/**
		 * Filter the error messages for PayPal checkout.
		 *
		 * @since 5.2.0
		 *
		 * @param array $messages Array of error messages.
		 */
		return apply_filters( 'tec_tickets_commerce_order_endpoint_error_messages', $messages );
	}

	/**
	 * Formats the order item name by truncating it to a specified length.
	 * If the text exceeds the maximum character length, it is truncated at the last space
	 * within the limit and an ellipsis is added at the end.
	 *
	 * @since 5.6.5
	 *
	 * @param string $text The original order item name text.
	 *
	 * @return string The formatted order item name text.
	 */
	public function format_order_item_name( string $text ): string {
		$max_character_length = 127;
		$ellipsis             = '...';
		$truncate_length      = $max_character_length - strlen( $ellipsis );

		if ( strlen( $text ) <= $max_character_length ) {
			return $text;
		}

		// Cut the text to the desired length
		$truncated_text = substr( $text, 0, $truncate_length );

		// Find the last space within the truncated text
		$last_space = strrpos( $truncated_text, ' ' );

		// Cut the text at the last space to avoid cutting in the middle of a word
		if ( $last_space !== false ) {
			$truncated_text = substr( $truncated_text, 0, $last_space );
		}

		// Add an ellipsis at the end
		$truncated_text .= $ellipsis;

		return $truncated_text;
	}
}
