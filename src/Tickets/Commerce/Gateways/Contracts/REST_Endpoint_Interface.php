<?php

namespace TEC\Tickets\Commerce\Gateways\Contracts;

/**
 * REST Endpoint Interface.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Contracts
 */
interface REST_Endpoint_Interface {

	/**
	 * Register the actual endpoint on WP Rest API.
	 *
	 * @since TBD made part of the REST_Endpoint_Interface
	 * @since 5.1.9
	 */
	public function register();

	/**
	 * Gets the Endpoint path for this route.
	 *
	 * @since TBD moved to Abstract_REST_Endpoint
	 * @since 5.1.9
	 *
	 * @return string
	 */
	public function get_endpoint_path();

	/**
	 * Get the REST API route URL.
	 *
	 * @since TBD moved to Abstract_REST_Endpoint
	 * @since 5.1.9
	 *
	 * @return string The REST API route URL.
	 */
	public function get_route_url();
}