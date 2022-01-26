<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe\REST;

use TEC\Tickets\Commerce\Gateways\Stripe\Client;
use TEC\Tickets\Commerce\Gateways\Stripe\Merchant;
use TEC\Tickets\Commerce\Gateways\Stripe\Refresh_Token;

use TEC\Tickets\Commerce\Gateways\Stripe\Webhooks;
use Tribe__Documentation__Swagger__Provider_Interface;

use WP_REST_Request;
use WP_REST_Server;

/**
 * Class Publishable_Key_Endpoint
 *
 * @todo Determine if this whole file is needed.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Stripe\REST
 */
class Payment_Intent_Secret_Endpoint implements Tribe__Documentation__Swagger__Provider_Interface {

	/**
	 * The REST API endpoint path.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected $path = '/commerce/stripe/paymentintent';

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
				'callback'            => [ $this, 'get_secret' ],
				'permission_callback' => '__return_true',
			]
		);

		$documentation->register_documentation_provider( $this->get_endpoint_path(), $this );
	}

	/**
	 * Fetch the key requested
	 *
	 * @since TBD
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return mixed
	 */
	public function get_secret( WP_REST_Request $request ) {

		$params = $request->get_json_params();

		/* @todo fixme
		if ( ! wp_verify_nonce( $params['nonce'], Assets::PUBLISHABLE_KEY_NONCE_ACTION ) ) {
			wp_send_json_error( 'Invalid nonce ' . $params['nonce'] );
		}
		*/

		$payment_intent = tribe( Client::class )->create_payment_intent();

		if ( ! empty( $payment_intent['client_secret'] ) ) {
			return $payment_intent['client_secret'];
		}

		return false;
	}

	/**
	 * Gets the Endpoint path for the on boarding process.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_endpoint_path() {
		return $this->path;
	}

	/**
	 * Get the REST API route URL.
	 *
	 * @since TBD
	 *
	 * @return string The REST API route URL.
	 */
	public function get_route_url() {
		$namespace = tribe( 'tickets.rest-v1.main' )->get_events_route_namespace();

		return rest_url( '/' . $namespace . $this->get_endpoint_path(), 'https' );
	}

	/**
	 * Sanitize a request argument based on details registered to the route.
	 *
	 * @since TBD
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
	 * {@inheritDoc}
	 *
	 * @TODO  We need to make sure Swagger documentation is present.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public function get_documentation() {
		return [];
	}
}
