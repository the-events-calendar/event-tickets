<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Requests;

/**
 * Stripe Requests.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Stripe;
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
	 * The Stripe API base URL.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	private static $api_base_url = 'https://api.stripe.com/v1';

	/**
	 * @inheritDoc
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