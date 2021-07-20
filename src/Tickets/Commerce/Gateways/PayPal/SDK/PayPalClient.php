<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal\SDK;

use TEC\Tickets\Commerce\Gateways\PayPal\SDK\Models\MerchantDetail;
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
class PayPalClient {

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
	public function getEnvironment() {
		/* @var MerchantDetail $merchant */
		$merchant = tribe( MerchantDetail::class );

		return 'sandbox' === $this->mode ?
			new SandboxEnvironment( $merchant->clientId, $merchant->clientSecret ) :
			new ProductionEnvironment( $merchant->clientId, $merchant->clientSecret );
	}

	/**
	 * Get http client.
	 *
	 * @since 5.1.6
	 *
	 * @return PayPalHttpClient
	 */
	public function getHttpClient() {
		return new PayPalHttpClient( $this->getEnvironment() );
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
	public function getApiUrl( $endpoint ) {
		$baseUrl = $this->getEnvironment()->baseUrl();

		return "{$baseUrl}/$endpoint";
	}

	/**
	 * Get PayPal homepage url.
	 *
	 * @since 5.1.6
	 *
	 * @return string
	 */
	public function getHomePageUrl() {
		$subdomain = 'sandbox' === $this->mode ? 'sandbox.' : '';

		return sprintf(
			'https://%1$spaypal.com/',
			$subdomain
		);
	}
}
