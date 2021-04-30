<?php

namespace Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce;

use Tribe\Tickets\REST\V1\Endpoints\PayPal_Commerce\Webhook;
use WP_REST_Server;

/**
 * Class REST
 *
 * @since   TBD
 * @package Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce
 */
class REST {

	/**
	 * The REST API namespace to use.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $namespace = '';

	/**
	 * The REST API documentation endpoint.
	 *
	 * @since TBD
	 *
	 * @var Tribe__Tickets__REST__V1__Endpoints__Swagger_Documentation
	 */
	public $documentation;

	public function __construct() {
		$this->namespace     = tribe( 'tickets.rest-v1.main' )->get_events_route_namespace();
		$this->documentation = tribe( 'tickets.rest-v1.endpoints.documentation' );
	}

	/**
	 * Register the endpoints for handling webhooks.
	 *
	 * @since TBD
	 */
	public function register_endpoints() {
		/** @var Webhook $endpoint */
		$endpoint = tribe( Webhook::class );

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

		$this->documentation->register_documentation_provider( $endpoint->path, $endpoint );
	}
}
