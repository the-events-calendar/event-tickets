<?php

namespace TEC\PaymentGateways\PayPalCommerce;

use TEC\PaymentGateways\PayPalCommerce\SDK\Models\MerchantDetail;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Core\SandboxEnvironment;

/**
 * Class PayPalClient
 *
 * @since TBD
 * @package TEC\PaymentGateways\PaypalCommerce
 *
 */
class PayPalClient {

	/**
	 * Environment mode.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $mode = null;

	/**
	 * PayPalClient constructor.
	 */
	public function __construct() {
		$this->mode = give_is_test_mode() ? 'sandbox' : 'live';
	}

	/**
	 * Get environment.
	 *
	 * @sicne 2.9.0
	 *
	 * @return ProductionEnvironment|SandboxEnvironment
	 */
	public function getEnvironment() {
		/* @var MerchantDetail $merchant */
		$merchant = tribe( MerchantDetail::class );

		return 'sandbox' === $this->mode ? new SandboxEnvironment( $merchant->clientId, $merchant->clientSecret ) : new ProductionEnvironment( $merchant->clientId, $merchant->clientSecret );
	}

	/**
	 * Get http client.
	 *
	 * @since TBD
	 *
	 * @return PayPalHttpClient
	 */
	public function getHttpClient() {
		return new PayPalHttpClient( $this->getEnvironment() );
	}

	/**
	 * Get api url.
	 *
	 * @since TBD
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
	 * @since TBD
	 *
	 * @return string
	 */
	public function getHomePageUrl() {
		return sprintf( 'https://%1$spaypal.com/', 'sandbox' === $this->mode ? 'sandbox.' : '' );
	}
}
