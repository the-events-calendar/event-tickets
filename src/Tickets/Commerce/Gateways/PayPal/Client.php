<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal;

use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Core\SandboxEnvironment;

/**
 * Class Client
 *
 * @since 5.1.6
 * @package TEC\Tickets\Commerce\Gateways\PayPal
 *
 */
class Client {
	/**
	 * Get environment.
	 *
	 * @since 5.1.6
	 *
	 * @return ProductionEnvironment|SandboxEnvironment
	 */
	public function get_environment() {
		$merchant = tribe( Merchant::class );

		return 'sandbox' === $merchant->get_mode() ?
			new SandboxEnvironment( $merchant->get_client_id(), $merchant->get_client_secret() ) :
			new ProductionEnvironment( $merchant->get_client_id(), $merchant->get_client_secret() );
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
		$base_url = $this->get_environment()->baseUrl();

		return "{$base_url}/$endpoint";
	}

	/**
	 * Get PayPal homepage url.
	 *
	 * @since 5.1.6
	 *
	 * @return string
	 */
	public function get_home_page_url() {
		$subdomain = tribe( Merchant::class )->is_sandbox() ? 'sandbox.' : '';

		return sprintf(
			'https://%1$spaypal.com/',
			$subdomain
		);
	}
}
