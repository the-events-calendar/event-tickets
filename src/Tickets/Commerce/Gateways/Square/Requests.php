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
	 * Get a response from the Square API with caching.
	 *
	 * @since TBD
	 *
	 * @param string $endpoint          The endpoint path.
	 * @param array  $query_args        Query args appended to the URL.
	 * @param array  $request_arguments Request arguments.
	 * @param bool   $raw               Whether to return the raw response.
	 *
	 * @return array|null
	 */
	public static function get_with_cache( $endpoint, array $query_args = [], array $request_arguments = [], $raw = false ): ?array {
		$cache_key = md5( wp_json_encode( [ $endpoint, $query_args, $request_arguments, $raw ] ) );
		$cache     = tribe_cache();

		$cached_response = $cache->get_transient( $cache_key );
		if ( false !== $cached_response ) {
			return $cached_response;
		}

		$response = self::get( $endpoint, $query_args, $request_arguments, $raw );

		$cache->set_transient( $cache_key, $response, MINUTE_IN_SECONDS * 10 );

		return $response;
	}

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

	public static function get_headers(): array {
		return [
			'Square-Version' => '2025-04-16',
		];
	}
}
