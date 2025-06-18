<?php
/**
 * WhoDat Interface.
 *
 * @since 5.3.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Contracts
 */

namespace TEC\Tickets\Commerce\Gateways\Contracts;

/**
 * WhoDat Interface
 *
 * @since 5.3.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Contracts
 */
// phpcs:ignore SlevomatCodingStandard.Classes.InterfaceName.InterfaceNameMustBeCapitalized, StellarWP.Classes.ValidClassName.NotSnakeCase
interface WhoDat_Interface {
	/**
	 * Returns the WhoDat URL to use.
	 *
	 * @since 5.24.0
	 *
	 * @return string
	 */
	public function get_api_base_url(): string;

	/**
	 * Returns the gateway-specific endpoint to use.
	 *
	 * @since 5.24.0
	 *
	 * @return string
	 */
	public function get_gateway_endpoint(): string;

	/**
	 * Get REST API endpoint URL for requests.
	 *
	 * @since 5.3.0 moved to Abstract_WhoDat.
	 * @since 5.1.9
	 *
	 * @param string $endpoint   The endpoint path.
	 * @param array  $query_args Query args appended to the URL.
	 *
	 * @return string The API URL.
	 */
	public function get_api_url( $endpoint, array $query_args = [] ): string;

	/**
	 * Send a GET request to WhoDat.
	 *
	 * @since 5.3.0 moved to Abstract_WhoDat.
	 * @since 5.1.9
	 *
	 * @param string $endpoint   The endpoint path.
	 * @param array  $query_args Query args appended to the URL.
	 *
	 * @return mixed|null
	 */
	public function get( $endpoint, array $query_args );

	/**
	 * Log WhoDat errors.
	 *
	 * @since 5.3.0    moved to Abstract_WhoDat and made public.
	 * @since 5.1.9
	 *
	 * @param string $type     The error type.
	 * @param string $message  The error message.
	 * @param string $url      The URL of the request.
	 */
	public function log_error( $type, $message, $url );

	/**
	 * Send a POST request to WhoDat.
	 *
	 * @since 5.3.0    moved to Abstract_WhoDat.
	 * @since 5.1.9
	 *
	 * @param string $endpoint           The endpoint path.
	 * @param array  $query_args         Query args appended to the URL.
	 * @param array  $request_arguments The request arguments.
	 *
	 * @return array|null
	 */
	public function post( $endpoint, array $query_args = [], array $request_arguments = [] );
}
