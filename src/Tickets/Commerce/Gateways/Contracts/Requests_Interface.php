<?php

namespace TEC\Tickets\Commerce\Gateways\Contracts;

/**
 * Requests Interface for gateways.
 *
 * @since 5.3.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Contracts;
 */
interface Requests_Interface {

	/**
	 * Send a GET request to the Stripe API.
	 *
	 * @since 5.3.0
	 *
	 * @param string $endpoint
	 * @param array  $query_args
	 * @param array  $request_arguments
	 * @param bool   $raw
	 *
	 * @return array|null
	 */
	public static function get( $endpoint, array $query_args = [], array $request_arguments = [], $raw = false );

	/**
	 * Send a POST request.
	 *
	 * @since 5.3.0
	 *
	 * @param string $endpoint
	 * @param array  $query_args
	 * @param array  $request_arguments
	 * @param bool   $raw
	 *
	 * @return array|null
	 */
	public static function post( $endpoint, array $query_args = [], array $request_arguments = [], $raw = false );

	/**
	 * Send a PATCH request to the Stripe API.
	 *
	 * @since 5.3.0
	 *
	 * @param string $endpoint
	 * @param array  $query_args
	 * @param array  $request_arguments
	 * @param bool   $raw
	 *
	 * @return array|null
	 */
	public static function patch( $endpoint, array $query_args = [], array $request_arguments = [], $raw = false );

	/**
	 * Send a DELETE request to the Stripe API.
	 *
	 * @since 5.3.0
	 *
	 * @param string $endpoint
	 * @param array  $query_args
	 * @param array  $request_arguments
	 * @param bool   $raw
	 *
	 * @return array|null
	 */
	public static function delete( $endpoint, array $query_args = [], array $request_arguments = [], $raw = false );

	/**
	 * Send a given method request to a given URL in the Stripe API.
	 *
	 * @since 5.3.0
	 *
	 * @param string $method
	 * @param string $url
	 * @param array  $query_args
	 * @param array  $request_arguments
	 * @param bool   $raw
	 * @param int    $retries Param used to determine the amount of time this particular request was retried.
	 *
	 * @return array|\WP_Error
	 */
	public static function request( $method, $url, array $query_args = [], array $request_arguments = [], $raw = false, $retries = 0 );

	/**
	 * Process Request responses to catch any error code and transform in a WP_Error.
	 * Returns the request array if no errors are found. Or a WP_Error object.
	 *
	 * @since 5.3.0
	 *
	 * @param array|\WP_Error $response Array of server data.
	 *
	 * @return array|\WP_Error
	 */
	public static function process_response( $response );

	/**
	 * Format user-facing errors to the list structure expected in the checkout script.
	 *
	 * @since 5.3.0
	 *
	 * @param \WP_Error $errors WP_Error instance.
	 *
	 * @return array[]
	 */
	public static function prepare_errors_to_display( \WP_Error $errors );

	/**
	 * Get REST API endpoint URL for requests.
	 *
	 * @since 5.3.0
	 *
	 * @param string $endpoint   The endpoint path.
	 * @param array  $query_args Query args appended to the URL.
	 *
	 * @return string The API URL.
	 *
	 */
	public static function get_api_url( $endpoint, array $query_args = [] );
}