<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe\REST;

use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_REST_Endpoint;
use TEC\Tickets\Commerce\Gateways\Stripe\Settings;

use TEC\Tickets\Commerce\Gateways\Stripe\Status;
use WP_REST_Server;
use WP_REST_Request;

use Tribe__Utils__Array as Arr;

/**
 * Class Webhook Endpoint.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Stripe\REST
 */
class Webhook_Endpoint extends Abstract_REST_Endpoint {

	const WATCHED_EVENTS = [
		'charge.succeeded',
	];

	/**
	 * The REST API endpoint path.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected $path = '/commerce/stripe/webhook';

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
				'callback'            => [ $this, 'handle_incoming_request' ],
				'permission_callback' => '__return_true',
			]
		);

		$documentation->register_documentation_provider( $this->get_endpoint_path(), $this );
	}

	public function create_order_args() {
		return [];
	}

	/**
	 * Handles the request that creates an order with Tickets Commerce and the Stripe gateway.
	 *
	 * @since TBD
	 *
	 * @param WP_REST_Request $request The request object.
	 */
	public function handle_incoming_request( WP_REST_Request $request ) {

		if ( ! $this->signature_is_valid( $request->get_header( 'Stripe-Signature' ), $request->get_body() ) ) {
			wp_send_json_error( 'Invalid Stripe Signature', 403 );
			exit();
		}

		$params = $request->get_json_params();

		if ( 'event' !== $params['object'] || ! in_array( $params['type'], static::WATCHED_EVENTS, true ) ) {
			wp_send_json_success( sprintf( '%s Webhook Event is not currently supported', esc_html( $params['type'] ) ), 200 );
			exit();
		}

		$handling_method = 'handle_' . str_replace( '.', '_', $params['type'] );

		if ( ! $this->{$handling_method}( $params ) ) {
			wp_send_json_error( '', 500 );
			exit();
		}

		wp_send_json_success( '', 200 );
		exit();
	}

	private function handle_charge_succeeded( $event ) {

		if ( empty( $event['data']['object']['payment_intent'] ) ) {
			return false;
		}

		$payment_intent = $event['data']['object']['payment_intent'];
		$status         = $event['data']['object']['status'];
		$order          = tec_tc_orders()->by_args( [
			'gateway_order_id' => $payment_intent,
		] )->first();

		// $this->maybe_update_order_status( $order, $status );

		return true;
	}

	/**
	 * Verifies the Stripe-Signature against the stored Webhook Signing Secret to make sure it's authentic.
	 *
	 * @since TBD
	 *
	 * @param string $header the Stripe-Signature request header
	 * @param string $body   the raw json request body
	 *
	 * @return bool
	 */
	private function signature_is_valid( $header, $body ) {
		$time = time();

		if ( ! $header || ! is_string( $body ) ) {
			return false;
		}

		$header_parts    = explode( ',', $header );
		$signing_secret  = tribe_get_option( Settings::$option_webhooks_signing_key );
		$signature_parts = [];

		if ( empty( $signing_secret ) ) {
			return false;
		}

		foreach ( $header_parts as $part ) {
			$pair = explode( '=', $part );

			if ( in_array( $pair[0], [ 't', 'v1' ], true ) ) {
				$signature_parts[ $pair[0] ] = $pair[1];
			}
		}

		if ( empty( $signature_parts['t'] ) || empty( $signature_parts['v1'] ) ) {
			return false;
		}

		if ( $time - (int) $signature_parts['t'] > 1 * MINUTE_IN_SECONDS ) { // @todo figure out the appropriate threshold here
			return false;
		}

		$signed_payload = implode( '', [ $signature_parts['t'], '.', $body ] );
		$hmac_signature = hash_hmac( 'sha256', $signed_payload, $signing_secret );

		return hash_equals( $signature_parts['v1'], $hmac_signature );
	}
}
