<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal;

use WP_REST_Server;

/**
 * Class REST
 *
 * @since   TBD
 * @package TEC\Tickets\Commerce\Gateways\PayPal
 */
class REST extends \tad_DI52_ServiceProvider {
	public function register() {
		$this->container->singleton( REST\Webhook::class, [ $this, 'boot_webhook_endpoint' ] );
		$this->container->singleton( REST\On_Boarding::class, [ $this, 'boot_on_boarding_endpoint' ] );
	}

	/**
	 * Properly initializes the Webhook class.
	 *
	 * @since TBD
	 *
	 * @return REST\Webhook
	 */
	public function boot_webhook_endpoint() {
		$messages = $this->container->make( 'tickets.rest-v1.messages' );

		return new REST\Webhook( $messages );
	}

	/**
	 * Properly initializes the On_Boarding class.
	 *
	 * @since TBD
	 *
	 * @return REST\On_Boarding
	 */
	public function boot_on_boarding_endpoint() {
		$messages = $this->container->make( 'tickets.rest-v1.messages' );

		return new REST\On_Boarding( $messages );
	}

	/**
	 * Register the endpoints for handling webhooks.
	 *
	 * @since 5.1.6
	 */
	public function register_endpoints() {
		$endpoint      = tribe( REST\Webhook::class );
		$namespace     = tribe( 'tickets.rest-v1.main' )->get_events_route_namespace();
		$documentation = tribe( 'tickets.rest-v1.endpoints.documentation' );

		register_rest_route(
			$namespace,
			$endpoint->get_endpoint_path(),
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'args'                => $endpoint->CREATE_args(),
				'callback'            => [ $endpoint, 'create' ],
				'permission_callback' => '__return_true',
			]
		);
		$documentation->register_documentation_provider( $endpoint->get_endpoint_path(), $endpoint );

		$endpoint = tribe( REST\On_Boarding::class );
		register_rest_route(
			$namespace,
			$endpoint->get_endpoint_path(),
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'args'                => $endpoint->CREATE_args(),
				'callback'            => [ $endpoint, 'create' ],
				'permission_callback' => '__return_true',
			]
		);

		$documentation->register_documentation_provider( $endpoint->get_endpoint_path(), $endpoint );

		$endpoint = tribe( REST\Orders::class );
		register_rest_route(
			$namespace,
			$endpoint->get_endpoint_path(),
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'args'                => $endpoint->CREATE_args(),
				'callback'            => [ $endpoint, 'create' ],
				'permission_callback' => '__return_true',
			]
		);

		$documentation->register_documentation_provider( $endpoint->get_endpoint_path(), $endpoint );
	}
}
