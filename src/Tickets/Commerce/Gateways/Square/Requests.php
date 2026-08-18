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
		$token_hash  = substr( md5( tribe( static::$merchant )->get_access_token() ), 0, 8 );
		$cache_key   = md5( wp_json_encode( [ $merchant_id, $token_hash, $endpoint, $query_args, $request_arguments, $raw ] ) );
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
	 * Sends a request to Square, keeping the access token current.
	 *
	 * Square access tokens expire, and nothing else in the plugin renews them, so the renewal happens
	 * here: this is the one place every Square API call passes through, and it is reached even on sites
	 * where the cron queue is not running.
	 *
	 * @since TBD
	 *
	 * @param string $method            The request method.
	 * @param string $url               The endpoint path or full URL.
	 * @param array  $query_args        Query args appended to the URL.
	 * @param array  $request_arguments Request arguments.
	 * @param bool   $raw               Whether to return the raw response.
	 * @param int    $retries           How many times this request has already been re-sent.
	 *
	 * @return array|\WP_Error
	 */
	public static function request( $method, $url, array $query_args = [], array $request_arguments = [], $raw = false, $retries = 0 ) {
		if ( 0 === $retries ) {
			tribe( Token_Refresher::class )->refresh_if_needed();
		}

		$response = parent::request( $method, $url, $query_args, $request_arguments, $raw, $retries );

		// Square can reject a token before its recorded expiration, for instance after a password reset.
		if ( 0 !== $retries || ! static::is_unauthorized_response( $response, (bool) $raw ) ) {
			return $response;
		}

		if ( ! tribe( Token_Refresher::class )->refresh_now( 'square_unauthorized' ) ) {
			return $response;
		}

		return static::request( $method, $url, $query_args, $request_arguments, $raw, $retries + 1 );
	}

	/**
	 * Whether Square turned the request down because of the access token.
	 *
	 * Square reports these under a plural `errors` key, which Abstract_Requests::process_response() leaves
	 * alone because it looks for the singular `error` shape, so the decoded body arrives here as-is.
	 *
	 * @since TBD
	 *
	 * @param mixed $response The response returned by the request.
	 * @param bool  $raw      Whether the response is an unprocessed HTTP response.
	 *
	 * @return bool
	 */
	protected static function is_unauthorized_response( $response, bool $raw = false ): bool {
		if ( $raw ) {
			return 401 === (int) wp_remote_retrieve_response_code( $response );
		}

		if ( ! is_array( $response ) || empty( $response['errors'] ) || ! is_array( $response['errors'] ) ) {
			return false;
		}

		foreach ( $response['errors'] as $error ) {
			if ( ! is_array( $error ) ) {
				continue;
			}

			if ( 'AUTHENTICATION_ERROR' === ( $error['category'] ?? '' ) ) {
				return true;
			}

			if ( in_array( $error['code'] ?? '', [ 'UNAUTHORIZED', 'ACCESS_TOKEN_EXPIRED', 'ACCESS_TOKEN_REVOKED' ], true ) ) {
				return true;
			}
		}

		return false;
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
