<?php
/**
 * Order Endpoint for the Square gateway.
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\REST
 */

namespace TEC\Tickets\Commerce\Gateways\Square\REST;

use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_REST_Endpoint;
use TEC\Tickets\Commerce\Gateways\Contracts\Traits\Requires_Login_To_Purchase;
use TEC\Tickets\Commerce\Gateways\Square\Gateway;
use TEC\Tickets\Commerce\Gateways\Square\Payment_Handler;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Gateways\Square\Order as Square_Order;
use TEC\Tickets\Commerce\Gateways\Square\Status;
use TEC\Tickets\Commerce\Sale_Window_Validator;
use TEC\Tickets\Commerce\Status\Created;
use TEC\Tickets\Commerce\Status\Denied;
use TEC\Tickets\Commerce\Status\Not_Completed;
use TEC\Tickets\Commerce\Status\Pending;
use TEC\Tickets\Commerce\Status\Voided;
use TEC\Tickets\Commerce\Success;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Post;
use RuntimeException;

/**
 * Class Order Endpoint.
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\REST
 */
class Order_Endpoint extends Abstract_REST_Endpoint {
	use Requires_Login_To_Purchase;


	/**
	 * The REST API endpoint path.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	protected string $path = '/commerce/square/order';

	/**
	 * Register the actual endpoint on WP Rest API.
	 *
	 * @since 5.24.0
	 */
	public function register(): void {
		$namespace     = tribe( 'tickets.rest-v1.main' )->get_events_route_namespace();
		$documentation = tribe( 'tickets.rest-v1.endpoints.documentation' );

		register_rest_route(
			$namespace,
			$this->get_endpoint_path(),
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'args'                => $this->create_order_args(),
				'callback'            => [ $this, 'handle_create_order' ],
				'permission_callback' => [ $this, 'can_purchase' ],
			]
		);

		$documentation->register_documentation_provider( $this->get_endpoint_path(), $this );
	}

	/**
	 * Arguments used for the endpoint.
	 *
	 * @since 5.24.0
	 *
	 * @return array
	 */
	public function create_order_args(): array {
		return [];
	}

	/**
	 * Handles the request that creates an order with Tickets Commerce and the Square gateway.
	 *
	 * @since 5.24.0
	 * @since 5.27.6.1 Removed order data from response for failed orders.
	 * @since 5.31.2 Holds the order's stock before the payment, so concurrent checkouts cannot pay for the same seats.
	 *            Returns an error, not a success, when the order refuses the paid status.
	 * @since 5.31.2 Returns an error when a ticket in the cart is outside its sale window.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_Error|WP_REST_Response An array containing the data on success or a WP_Error instance on failure.
	 */
	public function handle_create_order( WP_REST_Request $request ) {
		$response = [
			'success' => false,
		];

		$orders    = tribe( Order::class );
		$messages  = $this->get_error_messages();
		$data      = $request->get_json_params();
		$purchaser = $orders->get_purchaser_data( $data );

		if ( is_wp_error( $purchaser ) ) {
			return $purchaser;
		}

		if ( ! tribe( Cart::class )->has_items() ) {
			return new WP_Error(
				'tec-tc-empty-cart',
				$messages['empty-cart']
			);
		}

		$cart = tribe( Cart::class );

		$sale_window_validation = tribe( Sale_Window_Validator::class )->validate_items( $cart->get_items_in_cart() );

		if ( is_wp_error( $sale_window_validation ) ) {
			return $sale_window_validation;
		}

		// Create the order and hold its stock before the payment: concurrent checkouts see the hold.
		$order = $orders->create_from_cart_holding_stock( tribe( Gateway::class ), $purchaser );

		if ( is_wp_error( $order ) ) {
			return $order;
		}

		if ( ! $order instanceof WP_Post ) {
			return new WP_Error(
				'tec-tc-gateway-square-order-creation-failed',
				$messages['failed-order-creation']
			);
		}

		try {
			$square_order_id = tribe( Square_Order::class )->upsert_square_from_local_order( $order );
		} catch ( RuntimeException $e ) {
			return new WP_Error( 'tec-tc-gateway-square-failed-creating-order', $messages['failed-creating-order'] );
		}

		// Get the order object from the database, since the order object might have been updated by the Square_Order::upsert_square_from_local_order method.
		$order = tribe( Order::class )->get_from_gateway_order_id( $square_order_id );

		// For Square, we create a placeholder payment that will be updated later with the actual payment details.
		$payment = tribe( Payment_Handler::class )->create_payment_for_order( $data['payment_source_id'], $order, $square_order_id );

		if ( is_wp_error( $payment ) || empty( $payment ) ) {
			return new WP_Error( 'tec-tc-gateway-square-failed-creating-payment', $messages['failed-creating-payment'] );
		}

		if ( empty( $payment['id'] ) || empty( $payment['created_at'] ) || empty( $payment['status'] ) ) {
			return new WP_Error( 'tec-tc-gateway-square-failed-creating-payment', $messages['failed-creating-payment'] );
		}

		tribe( Square_Order::class )->add_payment_id( $order, $payment['id'] );

		if ( 'COMPLETED' !== $payment['status'] ) {
			return new WP_Error( 'tec-tc-gateway-square-failed-creating-payment', $messages['failed-creating-payment'] );
		}

		tec_tc_orders()
			->by_args(
				[
					'id' => $order->ID,
				]
			)
			->set_args(
				[
					'gateway_payload'  => $payment,
					'gateway_order_id' => $square_order_id,
				]
			)
			->save();

		$status  = tribe( Status::class )->convert_to_commerce_status( $payment['status'] );
		$updated = $orders->modify_status(
			$order->ID,
			$status->get_slug(),
			[
				'gateway_payload'  => $payment,
				'gateway_order_id' => $square_order_id,
			]
		);

		$orders->unlock_order( $order->ID );

		/*
		 * A refused paid status, e.g. a buyer whose stock hold expired before the payment and who lost the seat,
		 * must not be reported as a success. The order keeps the Square payment for a refund. An order a webhook
		 * already moved to this status is not a refusal.
		 */
		if ( ! $updated && $status->get_wp_slug() !== get_post_status( $order->ID ) ) {
			do_action(
				'tribe_log',
				'error',
				__METHOD__,
				[
					'message'          => 'The buyer paid, but the order refused the paid status.',
					'order_id'         => $order->ID,
					'gateway_order_id' => $square_order_id,
					'payment_id'       => $payment['id'],
				]
			);

			return new WP_Error(
				'tec-tc-gateway-square-unconfirmed-order',
				$messages['unconfirmed-order'],
				[ 'status' => 409 ]
			);
		}

		// Respond with the order data for Square usage.
		$response['success']    = true;
		$response['order_id']   = $order->ID;
		$response['payment_id'] = $payment['id'];

		// When we have success we clear the cart.
		tribe( Cart::class )->clear_cart();
		$response['redirect_url'] = add_query_arg( [ 'tc-order-id' => $square_order_id ], tribe( Success::class )->get_url() );

		// Remove the checkout screen hold.
		$orders->remove_on_checkout_screen_hold( $order->ID );

		return new WP_REST_Response( $response );
	}

	/**
	 * Arguments used for the fail order endpoint.
	 *
	 * @since 5.24.0
	 * @since 5.27.6.1 Added allowed failure statuses to the response for failed orders.
	 *
	 * @deprecated 5.29.0.1
	 *
	 * @return array
	 */
	public function fail_order_args(): array {
		_deprecated_function( __METHOD__, '5.29.0.1', 'This method is deprecated without replacement.' );

		return [
			'order_id'      => [
				'description'       => __( 'Order ID in Square', 'event-tickets' ),
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
	 * Handles the request that fails an order with Tickets Commerce and the Square gateway.
	 *
	 * @since 5.24.0
	 *
	 * @deprecated 5.27.6.1
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_Error|WP_REST_Response An array containing the data on success or a WP_Error instance on failure.
	 */
	public function handle_fail_order( WP_REST_Request $request ) {
		_deprecated_function( __METHOD__, '5.27.6.1', 'This endpoint is no longer used and will be removed in a future release.' );

		return new WP_REST_Response( [] );
	}

	/**
	 * Returns an array of error messages that are used by the API responses.
	 *
	 * @since 5.24.0
	 *
	 * @return array $messages Array of error messages.
	 */
	public function get_error_messages(): array {
		$messages = [
			'failed-order-creation'   => esc_html__( 'Creating new order failed, please refresh your checkout page.', 'event-tickets' ),
			'failed-creating-payment' => esc_html__( 'Creating new Square payment failed. Please try again.', 'event-tickets' ),
			'failed-creating-order'   => esc_html__( 'Creating new Square order failed. Please try again.', 'event-tickets' ),
			'order-not-found'         => esc_html__( 'Order not found, please restart your checkout process.', 'event-tickets' ),
			'failed-getting-payment'  => esc_html__( 'Your payment is invalid. Please try again.', 'event-tickets' ),
			'failed-payment'          => esc_html__( 'Your payment method has failed. Please try again.', 'event-tickets' ),
			'invalid-payment-status'  => esc_html__( 'Your payment status was not recognized. Please try again.', 'event-tickets' ),
			'empty-cart'              => esc_html__( 'Cannot generate an order for an empty cart, please select new items to checkout.', 'event-tickets' ),
			'unconfirmed-order'       => esc_html__( 'Your payment went through, but the tickets you selected are no longer available. Do not pay again: contact the site for a refund.', 'event-tickets' ),
		];
		/**
		 * Filter the error messages for Square checkout.
		 *
		 * @since 5.24.0
		 *
		 * @param array $messages Array of error messages.
		 */
		return (array) apply_filters( 'tec_tickets_commerce_square_order_endpoint_error_messages', $messages );
	}
}
