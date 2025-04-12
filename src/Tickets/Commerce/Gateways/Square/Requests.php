<?php

namespace TEC\Tickets\Commerce\Gateways\Square;

use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Requests;

/**
 * Stripe Requests.
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
	public static $merchant = Merchant::class;

	/**
	 * The Gateway class reference to use.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $gateway = Gateway::class;

	/**
	 * The Square API base URLs.
	 *
	 * @since TBD
	 *
	 * @var array
	 */
	private static $api_base_urls = [
		'live' => 'https://connect.squareup.com/v2',
		'sandbox' => 'https://connect.squareupsandbox.com/v2',
	];

	/**
	 * @inheritDoc
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
		$mode = $merchant->get_mode();

		return static::$api_base_urls[ $mode ] ?? static::$api_base_urls['sandbox'];
	}

}