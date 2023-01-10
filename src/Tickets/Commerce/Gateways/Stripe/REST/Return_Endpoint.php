<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe\REST;

use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_REST_Endpoint;
use TEC\Tickets\Commerce\Gateways\Stripe\Gateway;
use TEC\Tickets\Commerce\Gateways\Stripe\Merchant;
use TEC\Tickets\Commerce\Gateways\Stripe\Settings;
use TEC\Tickets\Commerce\Gateways\Stripe\Signup;
use TEC\Tickets\Commerce\Payments_Tab;
use Tribe\Tickets\Admin\Settings as Plugin_Settings;

use WP_REST_Server;
use WP_REST_Request;

/**
 * Class Return Endpoint.
 *
 * @since   5.3.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Stripe\REST
 */
class Return_Endpoint extends Abstract_REST_Endpoint {

	/**
	 * The REST API endpoint path.
	 *
	 * @since 5.3.0
	 *
	 * @var string
	 */
	protected $path = '/commerce/stripe/return';

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
				'methods'             => WP_REST_Server::READABLE,
				'args'                => $this->create_order_args(),
				'callback'            => [ $this, 'handle_stripe_return' ],
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
	 */
	public function handle_stripe_return( WP_REST_Request $request ) {
		$stripe_obj   = tribe_get_request_var( 'stripe' );
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
	 * Decode the payload received from WhoDat.
	 *
	 * @since 5.3.0
	 *
	 * @param string $payload json payload.
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
	 * Handle successful account connections.
	 *
	 * @since 5.3.0
	 *
	 * @param object $payload data returned from WhoDat.
	 */
	public function handle_connection_established( $payload ) {

		tribe( Merchant::class )->save_signup_data( (array) $payload );
		tribe( Settings::class )->setup_account_defaults();

		$validate = tribe( Merchant::class )->validate_account_is_permitted();

		if ( 'valid' !== $validate ) {
			tribe( Merchant::class )->set_merchant_unauthorized( $validate );
			$disconnect_url = tribe( Signup::class )->generate_disconnect_url();

			tribe( Merchant::class )->delete_signup_data();
			wp_redirect( $disconnect_url );
			exit();
		}

		tribe( Merchant::class )->unset_merchant_unauthorized();
		$url = tribe( Plugin_Settings::class )->get_url(
			[
				'tab'        => Payments_Tab::$slug,
				'tc-section' => Gateway::get_key(),
				'tc-status'  => 'stripe-signup-complete',
			]
		);

		wp_safe_redirect( $url );
		exit();
	}

	/**
	 * Handle unsuccessful account connections.
	 *
	 * @since 5.3.0
	 *
	 * @param object $payload data returned from WhoDat.
	 */
	public function handle_connection_error( $payload ) {
		$url = tribe( Plugin_Settings::class )->get_url( [
			'tab'             => Payments_Tab::$slug,
			'tc-section'      => Gateway::get_key(),
			'tc-stripe-error' => $payload->{'tc-stripe-error'},
		] );

		wp_safe_redirect( $url );
		exit();
	}

	/**
	 * Handle account disconnections.
	 *
	 * @since 5.3.0
	 */
	public function handle_connection_terminated( $reason = [] ) {
		tribe( Merchant::class )->delete_signup_data();
		Gateway::disable();

		$query_args = [
			'tab'                 => Payments_Tab::$slug,
			'tc-section'          => Gateway::get_key(),
			'stripe_disconnected' => 1,
		];

		$url_args = array_merge( $query_args, $reason );

		$url = tribe( Plugin_Settings::class )->get_url( $url_args );

		wp_safe_redirect( $url );
		exit();
	}
}
