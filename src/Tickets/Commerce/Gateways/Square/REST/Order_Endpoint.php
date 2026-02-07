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
use TEC\Tickets\Commerce\Gateways\Square\Gateway;
use TEC\Tickets\Commerce\Gateways\Square\Payment_Handler;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Gateways\Square\Order as Square_Order;
use TEC\Tickets\Commerce\Gateways\Square\Status;
use TEC\Tickets\Commerce\Stock_Validator;
use TEC\Tickets\Commerce\Status\Created;
use TEC\Tickets\Commerce\Status\Pending;
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
				'permission_callback' => '__return_true',
			]
		);

		register_rest_route(
			$namespace,
			$this->get_endpoint_path() . '/(?P<order_id>[0-9a-zA-Z_-]+)',
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'args'                => $this->fail_order_args(),
				'callback'            => [ $this, 'handle_fail_order' ],
				'permission_callback' => '__return_true',
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
				$messages['empty-cart'],
				[
					'purchaser' => $purchaser,
					'data'      => $data,
				]
			);
		}

		// Validate stock availability with database locking before creating order.
		$cart             = tribe( Cart::class );
		$stock_validation = tribe( Stock_Validator::class )->validate_cart_stock_with_lock( $cart );

		if ( is_wp_error( $stock_validation ) ) {
			return $stock_validation;
		}

		// If an order was created for this hash, we will attempt to update it, otherwise create a new one.
		$order = $orders->create_from_cart( tribe( Gateway::class ), $purchaser );
		if ( ! $order instanceof WP_Post ) {
			return new WP_Error(
				'tec-tc-gateway-square-order-creation-failed',
				$messages['failed-order-creation'],
				[
					'cart_items' => tribe( Cart::class )->get_items_in_cart(),
					'order'      => $order,
					'purchaser'  => $purchaser,
				]
			);
		}

		// Flag the order as on checkout screen hold.
		$orders->set_on_checkout_screen_hold( $order->ID );

		try {
			$square_order_id = tribe( Square_Order::class )->upsert_square_from_local_order( $order );
		} catch ( RuntimeException $e ) {
			return new WP_Error( 'tec-tc-gateway-square-failed-creating-order', $messages['failed-creating-order'], $order );
		}

		// Get the order object from the database, since the order object might have been updated by the Square_Order::upsert_square_from_local_order method.
		$order = tribe( Order::class )->get_from_gateway_order_id( $square_order_id );

		// For Square, we create a placeholder payment that will be updated later with the actual payment details.
		$payment = tribe( Payment_Handler::class )->create_payment_for_order( $data['payment_source_id'], $order, $square_order_id );

		if ( is_wp_error( $payment ) || empty( $payment ) ) {
			return new WP_Error( 'tec-tc-gateway-square-failed-creating-payment', $messages['failed-creating-payment'] );
		}

		if ( empty( $payment['id'] ) || empty( $payment['created_at'] ) || empty( $payment['status'] ) ) {
			return new WP_Error( 'tec-tc-gateway-square-failed-creating-payment', $messages['failed-creating-payment'], $order );
		}

		tribe( Square_Order::class )->add_payment_id( $order, $payment['id'] );

		if ( 'COMPLETED' !== $payment['status'] ) {
			return new WP_Error( 'tec-tc-gateway-square-failed-creating-payment', $messages['failed-creating-payment'], $order );
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

		$orders->modify_status(
			$order->ID,
			tribe( Status::class )->convert_to_commerce_status( $payment['status'] )->get_slug(),
			[
				'gateway_payload'  => $payment,
				'gateway_order_id' => $square_order_id,
			]
		);

		$orders->unlock_order( $order->ID );

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
	 *
	 * @return array
	 */
	public function fail_order_args(): array {
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
					if ( ! is_string( $value ) ) {
						return new WP_Error( 'rest_invalid_param', 'The failed status argument must be a string.', [ 'status' => 400 ] );
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
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_Error|WP_REST_Response An array containing the data on success or a WP_Error instance on failure.
	 */
	public function handle_fail_order( WP_REST_Request $request ) {
		$response = [
			'success' => false,
		];

		$messages      = $this->get_error_messages();
		$order_id      = $request->get_param( 'order_id' );
		$failed_status = $request->get_param( 'failed_status' );
		$failed_reason = $request->get_param( 'failed_reason' );

		$order = tec_tc_orders()->by_args(
			[
				'status'           => [
					tribe( Created::class )->get_wp_slug(),
					tribe( Pending::class )->get_wp_slug(),
				],
				'gateway_order_id' => $order_id,
			]
		)->first();

		if ( is_wp_error( $order ) || empty( $order ) ) {
			return new WP_Error( 'tec-tc-gateway-square-order-not-found', $messages['order-not-found'], $order );
		}

		$orders = tribe( Order::class );

		// Mark the order as failed.
		$orders->modify_status(
			$order->ID,
			$failed_status ?: 'failed',
			[
				'gateway_payload'  => [
					'failed_reason' => $failed_reason,
				],
				'gateway_order_id' => $order_id,
			]
		);

		$response['success'] = true;
		$response['status']  = $failed_status ?: 'failed';
		$response['message'] = $failed_reason ?: $messages['failed-payment'];

		return new WP_REST_Response( $response );
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
