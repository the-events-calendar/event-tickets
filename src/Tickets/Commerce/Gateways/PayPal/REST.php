<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal;

use WP_REST_Server;

/**
 * Class REST
 *
 * @since   5.1.6
 * @package TEC\Tickets\Commerce\Gateways\PayPal
 */
class REST {

	/**
	 * The REST API namespace to use.
	 *
	 * @since 5.1.6
	 *
	 * @var string
	 */
	public $namespace = '';

	/**
	 * The REST API documentation endpoint.
	 *
	 * @since 5.1.6
	 *
	 * @var \Tribe__Tickets__REST__V1__Endpoints__Swagger_Documentation
	 */
	public $documentation;

	public function __construct() {
		$this->namespace     = tribe( 'tickets.rest-v1.main' )->get_events_route_namespace();
		$this->documentation = tribe( 'tickets.rest-v1.endpoints.documentation' );
	}

	/**
	 * Register the endpoints for handling webhooks.
	 *
	 * @since 5.1.6
	 */
	public function register_endpoints() {
		/** @var PayPal_Webhook $endpoint */
		$endpoint = tribe( REST\Webhook::class );

		register_rest_route(
			$this->namespace,
			$endpoint->path,
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'args'                => $endpoint->CREATE_args(),
				'callback'            => [ $endpoint, 'create' ],
				'permission_callback' => '__return_true',
			]
		);
		$this->documentation->register_documentation_provider( $endpoint->get_endpoint_path(), $endpoint );

		$endpoint = tribe( REST\On_Boarding::class );
		register_rest_route(
			$this->namespace,
			$endpoint->get_endpoint_path(),
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'args'                => $endpoint->CREATE_args(),
				'callback'            => [ $endpoint, 'create' ],
				'permission_callback' => '__return_true',
			]
		);

		$this->documentation->register_documentation_provider( $endpoint->get_endpoint_path(), $endpoint );
	}
}
