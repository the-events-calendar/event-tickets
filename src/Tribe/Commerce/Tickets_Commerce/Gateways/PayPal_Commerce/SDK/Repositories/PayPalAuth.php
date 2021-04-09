<?php

namespace Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce\SDK\Repositories;

use TEC\ConnectClient\ConnectClient;
use TEC\Helpers\ArrayDataSet;
use Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce\SDK\PayPalClient;

class PayPalAuth {

	/**
	 * @since TBD
	 *
	 * @var PayPalClient
	 */
	private $payPalClient;

	/**
	 * @since TBD
	 *
	 * @var ConnectClient
	 */
	private $connectClient;

	/**
	 * PayPalAuth constructor.
	 *
	 * @since TBD
	 *
	 * @param PayPalClient  $payPalClient
	 * @param ConnectClient $connectClient
	 */
	public function __construct( PayPalClient $payPalClient, ConnectClient $connectClient ) {
		$this->payPalClient = $payPalClient;

		// @todo Need to figure out this object here.
		$this->connectClient = $connectClient;
	}

	/**
	 * Retrieves a token for the Client ID and Secret
	 *
	 * @since TBD
	 *
	 * @param string $client_id
	 * @param string $client_secret
	 *
	 * @return array
	 */
	public function getTokenFromClientCredentials( $client_id, $client_secret ) {
		$auth = base64_encode( "$client_id:$client_secret" );

		$request = wp_remote_post( $this->payPalClient->getApiUrl( 'v1/oauth2/token' ), [
			'headers' => [
				'Authorization' => sprintf( 'Basic %1$s', $auth ),
				'Content-Type'  => 'application/x-www-form-urlencoded',
			],
			'body'    => [
				'grant_type' => 'client_credentials',
			],
		] );

		// @todo Replace this with a new method somewhere else.
		return ArrayDataSet::camelCaseKeys( json_decode( wp_remote_retrieve_body( $request ), true ) );
	}

	/**
	 * Retrieves a token from the authorization code
	 *
	 * @since TBD
	 *
	 * @param string $authCode
	 * @param string $sharedId
	 * @param string $nonce
	 *
	 * @return array|null
	 */
	public function getTokenFromAuthorizationCode( $authCode, $sharedId, $nonce ) {
		$response = wp_remote_retrieve_body( wp_remote_post( $this->payPalClient->getApiUrl( 'v1/oauth2/token' ), [
			'headers' => [
				'Authorization' => sprintf( 'Basic %1$s', base64_encode( $sharedId ) ),
				'Content-Type'  => 'application/x-www-form-urlencoded',
			],
			'body'    => [
				'grant_type'    => 'authorization_code',
				'code'          => $authCode,
				'code_verifier' => $nonce, // Seller nonce.
			],
		] ) );

		// @todo Replace this with a new method somewhere else.
		return empty( $response ) ? null : ArrayDataSet::camelCaseKeys( json_decode( $response, true ) );
	}

	/**
	 * Retrieves a Partner Link for on-boarding
	 *
	 * @param $returnUrl
	 * @param $country
	 *
	 * @return array|null
	 */
	public function getSellerPartnerLink( $returnUrl, $country ) {
		$response = wp_remote_retrieve_body( wp_remote_post( sprintf( $this->connectClient->getApiUrl( 'paypal?mode=%1$s&request=partner-link' ), $this->payPalClient->mode ), [
			'body' => [
				'return_url'   => $returnUrl,
				'country_code' => $country,
			],
		] ) );

		return empty( $response ) ? null : json_decode( $response, true );
	}

	/**
	 * Get seller on-boarding details from seller.
	 *
	 * @since TBD
	 *
	 * @param string $accessToken
	 *
	 * @param string $merchantId
	 *
	 * @return array
	 */
	public function getSellerOnBoardingDetailsFromPayPal( $merchantId, $accessToken ) {
		$request = wp_remote_post( $this->connectClient->getApiUrl( sprintf( 'paypal?mode=%1$s&request=seller-status', $this->payPalClient->mode ) ), [
			'body' => [
				'merchant_id' => $merchantId,
				'token'       => $accessToken,
			],
		] );

		return json_decode( wp_remote_retrieve_body( $request ), true );
	}

	/**
	 * Get seller rest API credentials
	 *
	 * @since TBD
	 *
	 * @param string $accessToken
	 *
	 * @return array
	 */
	public function getSellerRestAPICredentials( $accessToken ) {
		$request = wp_remote_post( $this->connectClient->getApiUrl( sprintf( 'paypal?mode=%1$s&request=seller-credentials', $this->payPalClient->mode ) ), [
			'body' => [
				'token' => $accessToken,
			],
		] );

		return json_decode( wp_remote_retrieve_body( $request ), true );
	}
}
