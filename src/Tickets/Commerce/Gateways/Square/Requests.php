<?php
/**
 * Square Requests.
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square;
 */

namespace TEC\Tickets\Commerce\Gateways\Square;

use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Requests;
use TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects\SquareRateLimitedException;
/**
 * Square Requests.
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square;
 */
class Requests extends Abstract_Requests {

	/**
	 * The Merchant class reference to use.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	public static string $merchant = Merchant::class;

	/**
	 * The Gateway class reference to use.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	public static string $gateway = Gateway::class;

	/**
	 * The Square API base URLs.
	 *
	 * @since 5.24.0
	 *
	 * @var array
	 */
	private const API_BASE_URLS = [
		'live'    => 'https://connect.squareup.com/v2',
		'sandbox' => 'https://connect.squareupsandbox.com/v2',
	];

	/**
	 * Get the merchant ID.
	 *
	 * @since 5.24.0
	 *
	 * @return string The merchant ID.
	 */
	public static function get_merchant_id(): string {
		return tribe( static::$merchant )->get_merchant_id();
	}

	/**
	 * Get a response from the Square API with caching.
	 *
	 * @since 5.24.0
	 *
	 * @param string $endpoint          The endpoint path.
	 * @param array  $query_args        Query args appended to the URL.
	 * @param array  $request_arguments Request arguments.
	 * @param bool   $raw               Whether to return the raw response.
	 *
	 * @return array|null
	 */
	public static function get_with_cache( $endpoint, array $query_args = [], array $request_arguments = [], $raw = false ): ?array {
		$merchant_id = self::get_merchant_id();
		$cache_key   = md5( wp_json_encode( [ $merchant_id, $endpoint, $query_args, $request_arguments, $raw ] ) );
		$cache       = tribe_cache();

		$cached_response = $cache[ $cache_key ] ?? $cache->get_transient( $cache_key );
		if ( is_array( $cached_response ) ) {
			return $cached_response;
		}

		$response = self::get( $endpoint, $query_args, $request_arguments, $raw );

		$cache[ $cache_key ] = $response;
		$cache->set_transient( $cache_key, $response, MINUTE_IN_SECONDS * 10 );

		return $response;
	}

	/**
	 * Get REST API endpoint URL for requests.
	 *
	 * @since 5.24.0
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
	 * Process Request responses to catch any error code and transform in a WP_Error.
	 * Returns the request array if no errors are found. Or a WP_Error object.
	 *
	 * @since 5.24.0
	 *
	 * @param array|\WP_Error $response Array of server data.
	 *
	 * @return array|\WP_Error
	 * @throws SquareRateLimitedException If the response code is 429.
	 */
	public static function process_response( $response ) {
		$response_code = wp_remote_retrieve_response_code( $response );

		/**
		 * Filter the chance of triggering a rate limit exception.
		 *
		 * @since 5.24.0
		 *
		 * @param int $chance The chance of triggering a rate limit exception.
		 */
		$chance_of_triggering_rate_limit_exception = min( 100, max( 0, (int) apply_filters( 'tec_tickets_commerce_square_requests_chance_of_triggering_rate_limit_exception', 0 ) ) );

		$should_trigger = $chance_of_triggering_rate_limit_exception > wp_rand( 0, 99 );

		if ( $should_trigger || 429 === $response_code ) {
			throw new SquareRateLimitedException();
		}

		return parent::process_response( $response );
	}

	/**
	 * Get environment base URL based on current mode.
	 *
	 * @since 5.24.0
	 *
	 * @return string
	 */
	public static function get_environment_url() {
		$merchant = tribe( static::$merchant );
		$mode     = $merchant->get_mode();

		return static::API_BASE_URLS[ $mode ] ?? static::API_BASE_URLS['sandbox'];
	}

	/**
	 * Get the headers.
	 *
	 * @since 5.24.0
	 *
	 * @return array The headers.
	 */
	public static function get_headers(): array {
		return [
			'Square-Version' => '2025-05-21',
			'Content-Type'   => 'application/json',
			'Accept'         => 'application/json',
		];
	}
}
