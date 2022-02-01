<?php

namespace TEC\Tickets\Commerce\Gateways\Contracts;

/**
 * WhoDat Interface
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Contracts
 */
interface WhoDat_Interface {

	/**
	 * Send a GET request to WhoDat.
	 *
	 * @since TBD moved to Abstract_WhoDat.
	 * @since 5.1.9
	 *
	 * @param string $endpoint
	 * @param array  $query_args
	 *
	 * @return mixed|null
	 */
	public function get( $endpoint, array $query_args );

	/**
	 * Get REST API endpoint URL for requests.
	 *
	 * @since TBD moved to Abstract_WhoDat.
	 * @since 5.1.9
	 *
	 * @param string $endpoint   The endpoint path.
	 * @param array  $query_args Query args appended to the URL.
	 *
	 * @return string The API URL.
	 */
	public function get_api_url( $endpoint, array $query_args = [] );

	/**
	 * Log WhoDat errors.
	 *
	 * @since TBD    moved to Abstract_WhoDat and made public.
	 * @since 5.1.9
	 *
	 * @param string $type
	 * @param string $message
	 * @param string $url
	 */
	public function log_error( $type, $message, $url );

	/**
	 * Send a POST request to WhoDat.
	 *
	 * @since TBD    moved to Abstract_WhoDat.
	 * @since 5.1.9
	 *
	 * @param string $endpoint
	 * @param array  $query_args
	 * @param array  $request_arguments
	 *
	 * @return array|null
	 */
	public function post( $endpoint, array $query_args = [], array $request_arguments = [] );
}