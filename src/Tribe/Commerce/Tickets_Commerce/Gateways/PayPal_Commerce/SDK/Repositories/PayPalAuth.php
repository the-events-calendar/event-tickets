<?php

namespace Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce\SDK\Repositories;

use Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce\Connect_Client;
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
	 * @var Connect_Client
	 */
	private $connectClient;

	/**
	 * PayPalAuth constructor.
	 *
	 * @since TBD
	 *
	 * @param PayPalClient  $payPalClient
	 * @param Connect_Client $connectClient
	 */
	public function __construct( PayPalClient $payPalClient, Connect_Client $connectClient ) {
		$this->payPalClient  = $payPalClient;
		$this->connectClient = $connectClient;
	}

	/**
	 * Retrieves a token for the Client ID and Secret.
	 *
	 * @since TBD
	 *
	 * @param string $client_id     The Client ID.
	 * @param string $client_secret The Client Secret.
	 *
	 * @return array|null The token details response or null if there was a problem.
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

		if ( ! is_wp_error( $request ) ) {
			// @todo Log the error.
			return null;
		}

		$response = wp_remote_retrieve_body( $request );
		$response = @json_decode( $response, true );

		if ( ! is_array( $response ) ) {
			// @todo Log the error.
			return null;
		}

		return $response;
	}

	/**
	 * Retrieves a token from the authorization code.
	 *
	 * @since TBD
	 *
	 * @param string $sharedId Shared ID for merchant.
	 * @param string $authCode Authorization code from onboarding.
	 * @param string $nonce    Seller nonce from onboarding.
	 *
	 * @return array|null The token details response or null if there was a problem.
	 */
	public function getTokenFromAuthorizationCode( $sharedId, $authCode, $nonce ) {
		$request = wp_remote_post( $this->payPalClient->getApiUrl( 'v1/oauth2/token' ), [
			'headers' => [
				'Authorization' => sprintf( 'Basic %1$s', base64_encode( $sharedId ) ),
				'Content-Type'  => 'application/x-www-form-urlencoded',
			],
			'body'    => [
				'grant_type'    => 'authorization_code',
				'code'          => $authCode,
				'code_verifier' => $nonce,
			],
		] );

		if ( ! is_wp_error( $request ) ) {
			// @todo Log the error.
			return null;
		}

		$response = wp_remote_retrieve_body( $request );
		$response = @json_decode( $response, true );

		if ( ! is_array( $response ) ) {
			// @todo Log the error.
			return null;
		}

		return $response;
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
		$response = wp_remote_retrieve_body( wp_remote_post( sprintf( $this->connectClient->getApiUrl( 'paypal/?mode=%1$s&request=partner-link' ), $this->payPalClient->mode ), [
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
