<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe\REST;

use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_REST_Endpoint;
use TEC\Tickets\Commerce\Gateways\Stripe\Gateway;
use TEC\Tickets\Commerce\Gateways\Stripe\Merchant;
use Tribe__Settings;

use WP_REST_Server;
use WP_REST_Request;

/**
 * Class Return Endpoint.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Stripe\REST
 */
class Return_Endpoint extends Abstract_REST_Endpoint {

	/**
	 * The REST API endpoint path.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected $path = '/commerce/stripe/return';

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
				'methods'             => WP_REST_Server::READABLE,
				'args'                => $this->create_order_args(),
				'callback'            => [ $this, 'handle_stripe_return' ],
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
	 */
	public function handle_stripe_return( WP_REST_Request $request ) {
		$stripe_obj = tribe_get_request_var( 'stripe' );
		$disconnected = tribe_get_request_var( 'stripe_disconnected' );

		if ( ! empty( $stripe_obj ) ) {
			$response = $this->decode_payload( $stripe_obj );

			if ( ! empty( $response->{'tc-stripe-error'} ) ) {
				$this->handle_connection_error( $response );
			}

			if ( ! empty( $response->stripe_disconnected ) && $response->stripe_disconnected ) {
				$this->handle_connection_terminated();
			}

			$this->handle_connection_established( $response );
		}

		if ( ! empty( $disconnected ) ) {
			$this->handle_connection_terminated();
		}
	}

	/**
	 * Decode the payload received from WhoDat
	 *
	 * @since TBD
	 *
	 * @param string $payload
	 *
	 * @return object
	 */
	public function decode_payload( $payload ) {

		if ( empty( $payload ) ) {
			return;
		}

		return json_decode( base64_decode( $payload ) );
	}

	/**
	 * Handle successful account connections
	 *
	 * @since TBD
	 *
	 * @param object $payload data returned from WhoDat
	 */
	public function handle_connection_established( $payload ) {

		$tracking_id = tribe( Gateway::class )->generate_unique_tracking_id();
		$url = parse_url( $tracking_id );

		if ( $payload->whodat !== md5( $url['path'] ) ) {
			return;
		}

		tribe( Merchant::class )->save_signup_data( (array) $payload );

		$url = Tribe__Settings::instance()->get_url( [ 'tab' => 'payments', 'tc-section' => 'stripe' ] );

		wp_safe_redirect( $url );
		exit();
	}

	/**
	 * Handle unsuccessful account connections
	 *
	 * @since TBD
	 *
	 * @param object $payload data returned from WhoDat
	 */
	public function handle_connection_error( $payload ) {
		$url = Tribe__Settings::instance()->get_url( [ 'tab' => 'payments', 'tc-section' => 'stripe', 'tc-stripe-error' => $payload->{'tc-stripe-error'} ] );

		wp_safe_redirect( $url );
		exit();
	}

	/**
	 * Handle account disconnections
	 *
	 * @since TBD
	 */
	public function handle_connection_terminated() {
		tribe( Merchant::class )->save_signup_data([]);

		$url = Tribe__Settings::instance()->get_url( [ 'tab' => 'payments', 'tc-section' => 'stripe', 'stripe_disconnected' => 1 ] );

		wp_safe_redirect( $url );
		exit();
	}
}
