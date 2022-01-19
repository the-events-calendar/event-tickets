<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe\REST;

use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_REST_Endpoint;
use TEC\Tickets\Commerce\Gateways\Stripe\Gateway;
use TEC\Tickets\Commerce\Gateways\Stripe\Status;
use TEC\Tickets\Commerce\Order;

use TEC\Tickets\Commerce\Gateways\Stripe\Client;
use TEC\Tickets\Commerce\Status\Pending;

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

		$documentation->register_documentation_provider( $this->get_endpoint_path(), $this );
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

		$order = tribe( Order::class )->create_from_cart( tribe( Gateway::class ) );

		$stripe_request = tribe( Client::class )->create_payment_intent( $order->currency, $order->total_value->get_integer() );

		if ( is_wp_error( $stripe_request ) ) {
			return new WP_Error( 'tec-tc-gateway-stripe-failed-creating-payment-intent', $messages['failed-creating-payment-intent'], $order );
		}

		$payment_intent = json_decode( $stripe_request['body'] );

		if ( empty( $payment_intent->id ) || empty( $payment_intent->created ) ) {
			return new WP_Error( 'tec-tc-gateway-stripe-failed-creating-order', $messages['failed-creating-order'], $order );
		}

		$updated = tribe( Order::class )->modify_status( $order->ID, Pending::SLUG, [
			'gateway_payload'  => $payment_intent,
			'gateway_order_id' => $payment_intent->id,
		] );

		if ( is_wp_error( $updated ) ) {
			return $updated;
		}

		// Respond with the client_secret for Stripe Usage.
		$response['success']       = true;
		$response['client_secret'] = $payment_intent->client_secret;

		return new WP_REST_Response( $response );
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
			'failed-creating-payment-intent' => __( 'Creating new Stripe PaymentIntent failed. Please try again.', 'event-tickets' ),
			'failed-creating-order'          => __( 'Creating new Stripe order failed. Please try again.', 'event-tickets' ),
		];

		/**
		 * Filter the error messages for Stripe checkout.
		 *
		 * @since TBD
		 *
		 * @param array $messages Array of error messages.
		 */
		return apply_filters( 'tec_tickets_commerce_order_endpoint_error_messages', $messages );
	}
}
