<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe\REST;

use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_REST_Endpoint;

use WP_REST_Server;
use WP_REST_Request;

/**
 * Class Webhook Endpoint.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Stripe\REST
 */
class Webhook_Endpoint extends Abstract_REST_Endpoint {

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

		if ( ! $this->verify_signature( $request->get_header( 'Stripe-Signature' ), $request->get_body() ) ) {
			return;
		}

		$params = $request->get_json_params();
		wp_send_json( $params );
	}

	private function verify_signature( $header, $body ) {

		if ( ! $header || ! is_string( $body ) ) {
			return false;
		}

		$header_parts    = explode( ',', $header );
		$signing_secret  = 'whsec_s2SFmILd8auKsnvf3JGb2qsVZuSPDD1M'; // @todo move to database and settings field
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

		$signed_payload = implode( '', [ $signature_parts['t'], '.', $body ] );
		$hmac_signature = hash_hmac( 'sha256', $signed_payload, $signing_secret );

		return hash_equals( $signature_parts['v1'], $hmac_signature );
	}
}
