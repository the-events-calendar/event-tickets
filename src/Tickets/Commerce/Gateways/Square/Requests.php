<?php
/**
 * Square Requests.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square;
 */

namespace TEC\Tickets\Commerce\Gateways\Square;

use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Requests;

/**
 * Square Requests.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square;
 */
class Requests extends Abstract_Requests {

	/**
	 * The Merchant class reference to use.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static string $merchant = Merchant::class;

	/**
	 * The Gateway class reference to use.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static string $gateway = Gateway::class;

	/**
	 * The Square API base URLs.
	 *
	 * @since TBD
	 *
	 * @var array
	 */
	private const API_BASE_URLS = [
		'live'    => 'https://connect.squareup.com/v2',
		'sandbox' => 'https://connect.squareupsandbox.com/v2',
	];

	/**
	 * Endpoints that require a location ID.
	 *
	 * @since TBD
	 *
	 * @var array
	 */
	private const ENDPOINTS_REQUIRING_LOCATION = [
		'orders',
		'payments',
		'checkout',
		'refunds',
	];

	/**
	 * Get REST API endpoint URL for requests.
	 *
	 * @since TBD
	 *
	 * @param string $endpoint   The endpoint path.
	 * @param array  $query_args Query args appended to the URL.
	 *
	 * @return string The API URL.
	 */
	public static function get_api_url( $endpoint, array $query_args = [] ) {
		$base_url = static::get_environment_url();
		$endpoint = ltrim( $endpoint, '/' );

		return add_query_arg( $query_args, "{$base_url}/{$endpoint}" );
	}

	/**
	 * Get environment base URL based on current mode.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public static function get_environment_url() {
		$merchant = tribe( static::$merchant );
		$mode     = $merchant->get_mode();

		return static::API_BASE_URLS[ $mode ] ?? static::API_BASE_URLS['sandbox'];
	}

	/**
	 * Override the parent request method to handle location-specific endpoints.
	 *
	 * @since TBD
	 *
	 * @param string $method            The HTTP method.
	 * @param string $url               The endpoint URL.
	 * @param array  $query_args        The query arguments.
	 * @param array  $request_arguments The request arguments.
	 * @param bool   $raw               Whether to return the raw response.
	 * @param int    $retries           Number of retries.
	 *
	 * @return array|\WP_Error The response or error.
	 */
	public static function request( $method, $url, array $query_args = [], array $request_arguments = [], $raw = false, $retries = 0 ) {
		// Handle endpoints that need a location ID
		if ( static::endpoint_requires_location( $url ) ) {
			$url = static::maybe_add_location_to_endpoint( $url );
		}

		return parent::request( $method, $url, $query_args, $request_arguments, $raw, $retries );
	}

	/**
	 * Check if an endpoint requires a location ID.
	 *
	 * @since TBD
	 *
	 * @param string $endpoint The API endpoint.
	 *
	 * @return bool Whether the endpoint requires a location ID.
	 */
	private static function endpoint_requires_location( string $endpoint ): bool {
		// Already includes location in the URL
		if ( strpos( $endpoint, '/locations/' ) !== false ) {
			return false;
		}

		// If it's a full URL, extract just the endpoint
		if ( strpos( $endpoint, 'https://' ) === 0 ) {
			$parts = parse_url( $endpoint );
			$path = $parts['path'] ?? '';
			$path_parts = explode( '/', trim( $path, '/' ) );

			// Remove the v2 part if present
			if ( !empty( $path_parts ) && $path_parts[0] === 'v2' ) {
				array_shift( $path_parts );
			}

			$endpoint = $path_parts[0] ?? '';
		} else {
			// For simple endpoints, just get the first part before any slash
			$endpoint = explode( '/', $endpoint )[0] ?? '';
		}

		return in_array( $endpoint, self::ENDPOINTS_REQUIRING_LOCATION, true );
	}

	/**
	 * Add location ID to an endpoint if needed.
	 *
	 * @since TBD
	 *
	 * @param string $endpoint The API endpoint.
	 *
	 * @return string The endpoint with location ID if needed.
	 */
	private static function maybe_add_location_to_endpoint( string $endpoint ): string {
		// Get the location ID
		$location_id = tribe( static::$gateway )->get_location_id();

		if ( empty( $location_id ) ) {
			return $endpoint;
		}

		// If it's a full URL, we need to modify the path
		if ( strpos( $endpoint, 'https://' ) === 0 ) {
			$parts = parse_url( $endpoint );
			$path = $parts['path'] ?? '';

			// Check if it's a v2 URL
			$has_v2 = strpos( $path, '/v2/' ) === 0;
			$prefix = $has_v2 ? '/v2/locations/' . $location_id : '/locations/' . $location_id;

			// Replace the /v2/ part with our new path that includes the location
			if ( $has_v2 ) {
				$new_path = preg_replace( '|^/v2/|', $prefix . '/', $path );
			} else {
				$new_path = $prefix . $path;
			}

			// Reconstruct the URL
			$url = $parts['scheme'] . '://' . $parts['host'] . $new_path;
			if ( isset( $parts['query'] ) ) {
				$url .= '?' . $parts['query'];
			}

			return $url;
		}

		// For simple endpoints, just prepend the location path
		return 'locations/' . $location_id . '/' . ltrim( $endpoint, '/' );
	}
}
