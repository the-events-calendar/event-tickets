<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Requests;

class Requests extends Abstract_Requests {

	public static $merchant = Merchant::class;

	/**
	 * The Stripe API base URL
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	private static $api_base_url = 'https://api.stripe.com/v1';

	/**
	 * Get REST API endpoint URL for requests.
	 *
	 * @since TBD
	 *
	 * @param string $endpoint   The endpoint path.
	 * @param array  $query_args Query args appended to the URL.
	 *
	 * @return string The API URL.
	 *
	 */
	public static function get_api_url( $endpoint, array $query_args = [] ) {
		$base_url = static::get_environment_url();
		$endpoint = ltrim( $endpoint, '/' );

		return add_query_arg( $query_args, "{$base_url}/{$endpoint}" );
	}

	/**
	 * Get environment base URL.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public static function get_environment_url() {
		return static::$api_base_url;
	}

}