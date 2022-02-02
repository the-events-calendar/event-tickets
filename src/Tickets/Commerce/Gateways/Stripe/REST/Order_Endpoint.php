<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe\REST;

use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_REST_Endpoint;
use TEC\Tickets\Commerce\Gateways\Stripe\Gateway;
use TEC\Tickets\Commerce\Gateways\Stripe\Status;
use TEC\Tickets\Commerce\Order;

use TEC\Tickets\Commerce\Gateways\Stripe\Client;
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Pending;

use TEC\Tickets\Commerce\Success;

use Tribe__Utils__Array as Arr;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Class Order Endpoint.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Stripe\REST
 */
class Order_Endpoint extends Abstract_REST_Endpoint {

	/**
	 * The REST API endpoint path.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected $path = '/commerce/stripe/order';

	/**
	 * Register the actual endpoint on WP Rest API.
	 *
	 * @since TBD
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
	 * Arguments used for the endpoint
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public function create_order_args() {
		return [];
	}

	/**
	 * Handles the request that creates an order with Tickets Commerce and the Stripe gateway.
	 *
	 * @since TBD
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
		$purchaser = tribe( Order::class )->prepare_purchaser_data( $data );
		$order = tribe( Order::class )->create_from_cart( tribe( Gateway::class ), $purchaser );

		$payment_intent = tribe( Client::class )->update_payment_intent( $data );

		if ( is_wp_error( $payment_intent ) ) {
			return new WP_Error( 'tec-tc-gateway-stripe-failed-creating-payment-intent', $messages['failed-creating-payment-intent'], $order );
		}

		if ( empty( $payment_intent['id'] ) || empty( $payment_intent['created'] ) ) {
			return new WP_Error( 'tec-tc-gateway-stripe-failed-creating-order', $messages['failed-creating-order'], $order );
		}

		$updated = tribe( Order::class )->modify_status( $order->ID, Pending::SLUG, [
			'gateway_payload'  => $payment_intent,
			'gateway_order_id' => $payment_intent['id'],
		] );

		if ( is_wp_error( $updated ) ) {
			return $updated;
		}

		// Respond with the client_secret for Stripe Usage.
		$response['success']       = true;
		$response['client_secret'] = $payment_intent['client_secret'];
		$response['redirect_url'] = add_query_arg( [ 'tc-order-id' => $payment_intent['id'] ], tribe( Success::class )->get_url() );

		return new WP_REST_Response( $response );
	}

	/**
	 * Arguments used for the updating order endpoint.
	 *
	 * @since TBD
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
	 * @since TBD
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
		$gateway_order_id = $request->get_param( 'order_id' );

		$order = tec_tc_orders()->by_args( [
			'status'           => tribe( Pending::class )->get_wp_slug(),
			'gateway_order_id' => $gateway_order_id,
		] )->first();

		if ( is_wp_error( $order ) || empty( $order ) ) {
			return new WP_Error( 'tec-tc-gateway-stripe-order-not-found', $messages['order-not-found'], $order );
		}

		$client_secret  = $request->get_param( 'client_secret' );
		$payment_intent = tribe( Client::class )->get_payment_intent( $gateway_order_id, $client_secret );

		if ( is_wp_error( $payment_intent ) ) {
			return new WP_Error( 'tec-tc-gateway-stripe-failed-getting-payment-intent', $messages['failed-getting-payment-intent'], $order );
		}

		if ( empty( $payment_intent['id'] ) || $payment_intent['id'] !== $gateway_order_id ) {
			return new WP_Error( 'tec-tc-gateway-stripe-failed-payment-intent-id', $messages['failed-payment-intent-id'], $order );
		}

		if ( $payment_intent['client_secret'] !== $client_secret ) {
			return new WP_Error( 'tec-tc-gateway-stripe-failed-payment-intent-secret', $messages['failed-payment-intent-secret'], $order );
		}

		$payment_intent_status = Arr::get( $payment_intent, [ 'status' ] );
		$status                = tribe( Status::class )->convert_to_commerce_status( $payment_intent_status );

		if ( ! $status ) {
			return new WP_Error( 'tec-tc-gateway-stripe-invalid-payment-intent-status', $messages['invalid-payment-intent-status'], $payment_intent_status );
		}

		$updated = tribe( Order::class )->modify_status( $order->ID, $status->get_slug(), [
			'gateway_payload'  => $payment_intent,
			'gateway_order_id' => $payment_intent['id'],
		] );

		if ( is_wp_error( $updated ) ) {
			return $updated;
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
	 * @since TBD
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
	 * @since TBD
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
	 * @since TBD
	 *
	 * @return array $messages Array of error messages.
	 */
	public function get_error_messages() {
		$messages = [
			'failed-completing-payment-intent' => __( 'Completing the Stripe PaymentIntent failed. Please try again.', 'event-tickets' ),
			'failed-creating-payment-intent'   => __( 'Creating new Stripe PaymentIntent failed. Please try again.', 'event-tickets' ),
			'failed-creating-order'            => __( 'Creating new Stripe order failed. Please try again.', 'event-tickets' ),
		];

		/**
		 * Filter the error messages for Stripe checkout.
		 *
		 * @since TBD
		 *
		 * @param array $messages Array of error messages.
		 */
		return apply_filters( 'tec_tickets_commerce_stripe_order_endpoint_error_messages', $messages );
	}
}
