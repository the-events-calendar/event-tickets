<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal\SDK;

use TEC\Tickets\Commerce\Gateways\PayPal\SDK\Models\Merchant_Detail;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Core\SandboxEnvironment;

/**
 * Class PayPalClient
 *
 * @since 5.1.6
 * @package TEC\Tickets\Commerce\Gateways\PayPal\SDK
 *
 */
class PayPal_Client {

	/**
	 * Environment mode.
	 *
	 * @since 5.1.6
	 *
	 * @var string
	 */
	public $mode = null;

	/**
	 * PayPalClient constructor.
	 */
	public function __construct() {
		$this->mode = tribe_tickets_commerce_is_test_mode() ? 'sandbox' : 'live';
	}

	/**
	 * Get environment.
	 *
	 * @since 5.1.6
	 *
	 * @return ProductionEnvironment|SandboxEnvironment
	 */
	public function get_environment() {
		/* @var Merchant_Detail $merchant */
		$merchant = tribe( Merchant_Detail::class );

		return 'sandbox' === $this->mode ?
			new SandboxEnvironment( $merchant->client_id, $merchant->client_secret ) :
			new ProductionEnvironment( $merchant->client_id, $merchant->client_secret );
	}

	/**
	 * Get http client.
	 *
	 * @since 5.1.6
	 *
	 * @return PayPalHttpClient
	 */
	public function get_http_client() {
		return new PayPalHttpClient( $this->get_environment() );
	}

	/**
	 * Get api url.
	 *
	 * @since 5.1.6
	 *
	 * @param string $endpoint
	 *
	 * @return string
	 */
	public function get_api_url( $endpoint ) {
		$baseUrl = $this->get_environment()->baseUrl();

		return "{$baseUrl}/$endpoint";
	}

	/**
	 * Get PayPal homepage url.
	 *
	 * @since 5.1.6
	 *
	 * @return string
	 */
	public function get_home_page_url() {
		$subdomain = 'sandbox' === $this->mode ? 'sandbox.' : '';

		return sprintf(
			'https://%1$spaypal.com/',
			$subdomain
		);
	}
}
