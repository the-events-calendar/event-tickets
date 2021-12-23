<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe\REST;

use TEC\Tickets\Commerce\Gateways\Stripe\Client;
use TEC\Tickets\Commerce\Gateways\Stripe\Merchant;
use TEC\Tickets\Commerce\Gateways\Stripe\Refresh_Token;

use TEC\Tickets\Commerce\Gateways\Stripe\Signup;
use TEC\Tickets\Commerce\Gateways\Stripe\Webhooks;
use TEC\Tickets\Commerce\Gateways\Stripe\WhoDat;
use TEC\Tickets\Commerce\Notice_Handler;
use Tribe__Documentation__Swagger__Provider_Interface;
use Tribe__Settings;
use Tribe__Utils__Array as Arr;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;


/**
 * Class On_Boarding_Endpoint
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Stripe\REST
 */
class On_Boarding_Endpoint implements Tribe__Documentation__Swagger__Provider_Interface {

	/**
	 * The REST API endpoint path.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected $path = '/commerce/stripe/on-boarding';

	/**
	 * Register the actual endpoint on WP Rest API.
	 *
	 * @since TBD
	 */
	public function register() {
		$namespace     = tribe( 'tickets.rest-v1.main' )->get_events_route_namespace();
		$documentation = tribe( 'tickets.rest-v1.endpoints.documentation' );

		/*
		register_rest_route(
			$namespace,
			$this->get_endpoint_path(),
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'args'                => $this->fetch_token_args(),
				'callback'            => [ $this, 'handle_fetch_token' ],
				'permission_callback' => '__return_true',
			]
		);
		*/

		register_rest_route(
			$namespace,
			$this->get_endpoint_path(),
			[
				'methods'             => WP_REST_Server::READABLE,
//				'args'                => $this->signup_redirect_args(),
				'callback'            => [ $this, 'start_onboarding' ],
				'permission_callback' => '__return_true',
			]
		);

		$documentation->register_documentation_provider( $this->get_endpoint_path(), $this );
	}

	public function start_onboarding( WP_REST_Request $request ) {
		return tribe( WhoDat::class )->connect_account();
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
	 * Gets the Return URL pointing to this on boarding route.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_return_url( $hash = null ) {
		$arguments = [];

		if ( $hash ) {
			$arguments['hash'] = $hash;
		}

		return add_query_arg( $arguments, $this->get_route_url() );
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
