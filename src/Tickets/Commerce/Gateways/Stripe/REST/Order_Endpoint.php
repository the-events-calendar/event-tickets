<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe\REST;

use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Checkout;
use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_REST_Endpoint;
use TEC\Tickets\Commerce\Gateways\Stripe\Gateway;
use TEC\Tickets\Commerce\Gateways\Stripe\Payment_Intent;
use TEC\Tickets\Commerce\Gateways\Stripe\Payment_Intent_Handler;
use TEC\Tickets\Commerce\Gateways\Stripe\Status;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Stock_Validator;

use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Created;
use TEC\Tickets\Commerce\Status\Pending;
use TEC\Tickets\Commerce\Success;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Post;

/**
 * Class Order Endpoint.
 *
 * @since 5.3.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Stripe\REST
 */
class Order_Endpoint extends Abstract_REST_Endpoint {

	/**
	 * The REST API endpoint path.
	 *
	 * @since 5.3.0
	 *
	 * @var string
	 */
	protected string $path = '/commerce/stripe/order';

	/**
	 * Register the actual endpoint on WP Rest API.
	 *
	 * @since 5.3.0
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
			$this->get_endpoint_path() . '/(?P<order_id>[0-9a-zA-Z_-]+)',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'args'                => $this->update_order_args(),
				'callback'            => [ $this, 'handle_update_order' ],
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
	 * @since 5.3.0
	 *
	 * @return array
	 */
	public function create_order_args() {
		return [];
	}

	/**
	 * Handles the request that creates an order with Tickets Commerce and the Stripe gateway.
	 *
	 * @since 5.3.0
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

		// Validate stock availability with database locking before creating payment intent.
		$cart             = tribe( Cart::class );
		$stock_validation = tribe( Stock_Validator::class )->validate_cart_stock_with_lock( $cart );

		if ( is_wp_error( $stock_validation ) ) {
			return $stock_validation;
		}

		// If an order was created for this hash, we will attempt to update it, otherwise create a new one.
		$order = $orders->create_from_cart( tribe( Gateway::class ), $purchaser );
		if ( ! $order instanceof WP_Post ) {
			return new WP_Error(
				'tec-tc-gateway-stripe-order-creation-failed',
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

		$payment_intent = tribe( Payment_Intent_Handler::class )->update_payment_intent( $data, $order );

		if ( is_wp_error( $payment_intent ) ) {
			return new WP_Error( 'tec-tc-gateway-stripe-failed-creating-payment-intent', $messages['failed-creating-payment-intent'], $order );
		}

		if ( empty( $payment_intent['id'] ) || empty( $payment_intent['created'] ) ) {
			return new WP_Error( 'tec-tc-gateway-stripe-failed-creating-order', $messages['failed-creating-order'], $order );
		}

		tec_tc_orders()
			->by_args(
				[
					'id' => $order->ID,
				]
			)
			->set_args(
				[
					'gateway_payload'  => $payment_intent,
					'gateway_order_id' => $payment_intent['id'],
				]
			)
			->save();

		$status = tribe( Status::class )->convert_to_commerce_status( $payment_intent['status'] );

		if ( ! in_array( $status->get_slug(), [ Created::SLUG, Pending::SLUG ], true ) ) {
			$orders->unlock_order( $order->ID );

			return new WP_Error(
				'tec-tc-gateway-stripe-failed-payment',
				$messages['failed-payment'],
				[
					'order_id'     => $order->ID,
					'status'       => $status->get_slug(),
					'payment_data' => $data,
				]
			);
		}

		// We will attempt to update the order status to the one returned by Stripe.
		$orders->modify_status(
			$order->ID,
			$status->get_slug(),
			[
				'gateway_payload'  => $payment_intent,
				'gateway_order_id' => $payment_intent['id'],
			]
		);

		$orders->unlock_order( $order->ID );

		// Respond with the client_secret for Stripe Usage.
		$response['success']       = true;
		$response['order_id']      = $order->ID;
		$response['client_secret'] = $payment_intent['client_secret'];
		$response['return_url']    = add_query_arg( [ Cart::$cookie_query_arg => tribe( Cart::class )->get_cart_hash() ], tribe( Checkout::class )->get_url() );

		if ( $status->get_slug() === Pending::SLUG ) {
			$response['redirect_url'] = add_query_arg( [ 'tc-order-id' => $payment_intent['id'] ], tribe( Success::class )->get_url() );
		}

		return new WP_REST_Response( $response );
	}

	/**
	 * Arguments used for the updating order endpoint.
	 *
	 * @since 5.3.0
	 *
	 * @return array
	 */
	public function update_order_args() {
		return [
			'order_id'      => [
				'description'       => __( 'Order ID (Payment Intent ID) in Stripe', 'event-tickets' ),
				'required'          => true,
				'type'              => 'string',
				'validate_callback' => static function ( $value ) {
					if ( ! is_string( $value ) ) {
						return new WP_Error( 'rest_invalid_param', 'The Order ID (Payment Intent ID) argument must be a string.', [ 'status' => 400 ] );
					}

					return $value;
				},
				'sanitize_callback' => [ $this, 'sanitize_callback' ],
			],
			'client_secret' => [
				'description'       => __( 'Client Secret from Stripe', 'event-tickets' ),
				'required'          => false,
				'type'              => 'string',
				'validate_callback' => static function ( $value ) {
					if ( ! is_string( $value ) ) {
						return new WP_Error( 'rest_invalid_param', 'The Client Secret argument must be a string.', [ 'status' => 400 ] );
					}

					return $value;
				},
				'sanitize_callback' => [ $this, 'sanitize_callback' ],
			],
		];
	}

	/**
	 * Handles the request that creates an order with Tickets Commerce and the Stripe gateway.
	 *
	 * @since 5.3.0
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_Error|WP_REST_Response An array containing the data on success or a WP_Error instance on failure.
	 */
	public function handle_update_order( WP_REST_Request $request ) {
		$response = [
			'success' => false,
		];

		$messages         = $this->get_error_messages();
		$gateway_order_id = $request->get_param( 'order_id' );

		$order = tec_tc_orders()->by_args(
			[
				'status'           => [
					tribe( Created::class )->get_wp_slug(),
					tribe( Pending::class )->get_wp_slug(),
				], // Potentially change this to method that fetch all non-final statuses.
				'gateway_order_id' => $gateway_order_id,
			]
		)->first();

		if ( is_wp_error( $order ) || empty( $order ) ) {
			return new WP_Error( 'tec-tc-gateway-stripe-order-not-found', $messages['order-not-found'], $order );
		}

		$orders = tribe( Order::class );

		// Flag the order as on checkout screen hold.
		$orders->set_on_checkout_screen_hold( $order->ID );

		$client_secret  = $request->get_param( 'client_secret' );
		$payment_intent = Payment_Intent::get( $gateway_order_id );

		if ( is_wp_error( $payment_intent ) ) {
			return new WP_Error( 'tec-tc-gateway-stripe-failed-getting-payment-intent', $messages['failed-getting-payment-intent'], $order );
		}

		if ( empty( $payment_intent['id'] ) || $payment_intent['id'] !== $gateway_order_id ) {
			return new WP_Error( 'tec-tc-gateway-stripe-failed-payment-intent-id', $messages['failed-getting-payment-intent'], $order );
		}

		if ( $payment_intent['client_secret'] !== $client_secret ) {
			return new WP_Error( 'tec-tc-gateway-stripe-failed-payment-intent-secret', $messages['failed-payment-intent-secret'], $order );
		}

		$status = tribe( Status::class )->convert_payment_intent_to_commerce_status( $payment_intent );

		if ( ! $status ) {
			return new WP_Error( 'tec-tc-gateway-stripe-invalid-payment-intent-status', $messages['invalid-payment-intent-status'], [ 'status' => $status ] );
		}

		$orders->modify_status(
			$order->ID,
			$status->get_slug(),
			[
				'gateway_payload'  => $payment_intent,
				'gateway_order_id' => $payment_intent['id'],
			]
		);

		if ( ! in_array( $status->get_slug(), [ Completed::SLUG, Pending::SLUG ], true ) ) {
			return new WP_Error(
				'tec-tc-gateway-stripe-failed-payment',
				$messages['failed-payment'],
				[
					'order_id'     => $order->ID,
					'status'       => $status->get_slug(),
					'payment_data' => $payment_intent,
				]
			);
		}

		// Respond with the client_secret for Stripe Usage.
		$response['success']          = true;
		$response['status']           = $status->get_slug();
		$response['order_id']         = $order->ID;
		$response['gateway_order_id'] = $gateway_order_id;

		// When we have success we clear the cart.
		tribe( Cart::class )->clear_cart();

		$response['redirect_url'] = add_query_arg( [ 'tc-order-id' => $gateway_order_id ], tribe( Success::class )->get_url() );

		return new WP_REST_Response( $response );
	}

	/**
	 * Arguments used for the fail order endpoint.e
	 *
	 * @since 5.3.0
	 *
	 * @return array
	 */
	public function fail_order_args() {
		return [
			'order_id'      => [
				'description'       => __( 'Order ID in Stripe', 'event-tickets' ),
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
	 * Handles the request that creates an order with Tickets Commerce and the Stripe gateway.
	 *
	 * @since 5.3.0
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_Error|WP_REST_Response An array containing the data on success or a WP_Error instance on failure.
	 */
	public function handle_fail_order( WP_REST_Request $request ) {

	}

	/**
	 * Returns an array of error messages that are used by the API responses.
	 *
	 * @since 5.3.0
	 *
	 * @return array $messages Array of error messages.
	 */
	public function get_error_messages() {
		$messages = [
			'failed-order-creation'            => __( 'Creating new order failed, please refresh your checkout page.', 'event-tickets' ),
			'failed-completing-payment-intent' => __( 'Completing the Stripe PaymentIntent failed. Please try again.', 'event-tickets' ),
			'failed-creating-payment-intent'   => __( 'Creating new Stripe PaymentIntent failed. Please try again.', 'event-tickets' ),
			'failed-creating-order'            => __( 'Creating new Stripe order failed. Please try again.', 'event-tickets' ),
			'order-not-found'                  => __( 'Order not found, please restart your checkout process.', 'event-tickets' ),
			'failed-getting-payment-intent'    => __( 'Your payment is invalid. Please try again.', 'event-tickets' ),
			'failed-payment-intent-secret'     => __( 'Your payment failed security verification with Gateway. Please try again.', 'event-tickets' ),
			'failed-payment'                   => __( 'Your payment method has failed. Please try again.', 'event-tickets' ),
			'invalid-payment-intent-status'    => __( 'Your payment status was not recognized. Please try again.', 'event-tickets' ),
			'empty-cart'                       => __( 'Cannot generate an order for an empty cart, please select new items to checkout.', 'event-tickets' ),
		];
		/**
		 * Filter the error messages for Stripe checkout.
		 *
		 * @since 5.3.0
		 *
		 * @param array $messages Array of error messages.
		 */
		return apply_filters( 'tec_tickets_commerce_stripe_order_endpoint_error_messages', $messages );
	}
}
