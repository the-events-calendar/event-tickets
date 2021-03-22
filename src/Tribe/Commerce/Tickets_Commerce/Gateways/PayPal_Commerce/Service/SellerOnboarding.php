<?php

namespace TEC\PaymentGateways\PayPalCommerce\Service;

/**
 * Class SellerOnboarding
 *
 * @package TEC\PaymentGateways\PayPalCommerce\Service
 */
class SellerOnboarding extends ApiService {

	/**
	 * @var string
	 */
	private $seller_merchant_id;

	/**
	 * @var string
	 */
	private $seller_token;

	/**
	 * Sets the Seller Merchant ID
	 *
	 * @param string $merchant_id
	 *
	 * @return $this
	 */
	public function set_seller_merchantid( $merchant_id ) {
		$this->seller_merchant_id = $merchant_id;

		return $this;
	}

	/**
	 * Sets the Seller Token
	 *
	 * @param string $token
	 *
	 * @return $this
	 */
	public function set_seller_token( $token ) {
		$this->seller_token = $token;

		return $this;
	}

	/**
	 * Retrieves the status for an on-boarded Seller
	 *
	 * @see https://developer.paypal.com/docs/api/partner-referrals/v1/#merchant-integration
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function get_seller_status() {
		$request = curl_init();

		$api_url = $this->get_api_url( "v1/customer/partners/{$this->get_merchant_id()}/merchant-integrations/{$this->seller_merchant_id}" );

		curl_setopt_array( $request, [
			CURLOPT_URL            => $api_url,
			CURLOPT_HTTPHEADER     => [
				'Accept: application/json',
				"Authorization: Bearer {$this->seller_token}",
			],
			CURLOPT_RETURNTRANSFER => 1,
		] );

		$response = curl_exec( $request );

		$error_response = curl_errno( $request ) ? curl_error( $response ) : null;

		curl_close( $request );

		if ( null !== $error_response ) {
			throw new Exception( 'Error: ', $error_response );
		}

		return json_decode( $response );
	}

	/**
	 * Retrieves the credentials for the Seller account
	 *
	 * @see https://developer.paypal.com/docs/api/partner-referrals/v1/#merchant-integration_credentials
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function get_seller_credentials() {
		$request = curl_init();

		$api_url = $this->get_api_url( "v1/customer/partners/{$this->get_merchant_id()}/merchant-integrations/credentials" );

		curl_setopt_array( $request, [
			CURLOPT_URL            => $api_url,
			CURLOPT_HTTPHEADER     => [
				'Accept: application/json',
				"Authorization: Bearer {$this->seller_token}",
			],
			CURLOPT_RETURNTRANSFER => 1,
		] );

		$response = curl_exec( $request );

		if ( curl_errno( $request ) ) {
			throw new Exception( 'Error: ', curl_error( $response ) );
		}

		$response = json_decode( $response );

		curl_close( $request );

		return $response;
	}
}
