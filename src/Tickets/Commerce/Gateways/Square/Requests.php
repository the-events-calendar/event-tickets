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
}
