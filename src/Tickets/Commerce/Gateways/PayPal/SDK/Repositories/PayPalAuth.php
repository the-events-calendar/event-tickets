<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal\SDK\Repositories;

use TEC\Tickets\Commerce\Gateways\PayPal\Connect_Client;
use TEC\Tickets\Commerce\Gateways\PayPal\SDK\PayPalClient;

class PayPalAuth {

	/**
	 * @since 5.1.6
	 *
	 * @var PayPalClient
	 */
	private $payPalClient;

	/**
	 * @since 5.1.6
	 *
	 * @var Connect_Client
	 */
	private $connectClient;

	/**
	 * PayPalAuth constructor.
	 *
	 * @since 5.1.6
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
	 * @since 5.1.6
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

		if ( is_wp_error( $request ) ) {
			tribe( 'logger' )->log_error( sprintf(
				// Translators: %s: The error message.
				__( 'PayPal request error: %s', 'event-tickets' ),
				$request->get_error_message()
			), 'tickets-commerce-paypal-commerce' );

			return null;
		}

		$response = wp_remote_retrieve_body( $request );
		$response = @json_decode( $response, true );

		if ( ! is_array( $response ) ) {
			tribe( 'logger' )->log_error( __( 'Unexpected PayPal response when getting token from client credentials', 'event-tickets' ), 'tickets-commerce-paypal-commerce' );

			return null;
		}

		return $response;
	}

	/**
	 * Retrieves a token from the authorization code.
	 *
	 * @since 5.1.6
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

		if ( is_wp_error( $request ) ) {
			tribe( 'logger' )->log_error( sprintf(
				// Translators: %s: The error message.
				__( 'PayPal request error: %s', 'event-tickets' ),
				$request->get_error_message()
			), 'tickets-commerce-paypal-commerce' );

			return null;
		}

		$response = wp_remote_retrieve_body( $request );
		$response = @json_decode( $response, true );

		if ( ! is_array( $response ) ) {
			tribe( 'logger' )->log_error( __( 'Unexpected PayPal response when getting token from authorization code', 'event-tickets' ), 'tickets-commerce-paypal-commerce' );

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
		$request = wp_remote_post( sprintf( $this->connectClient->get_api_url( 'paypal-commerce/?mode=%1$s&request=partner-link' ), $this->payPalClient->mode ), [
			'body' => [
				'return_url'   => $returnUrl,
				'country_code' => $country,
			],
			// @todo Remove this when SSL is fixed.
			'sslverify' => false,
		] );

		if ( is_wp_error( $request ) ) {
			tribe( 'logger' )->log_error( sprintf(
				// Translators: %s: The error message.
				__( 'PayPal Commerce Connect request error: %s', 'event-tickets' ),
				$request->get_error_message()
			), 'tickets-commerce-paypal-commerce' );

			return null;
		}

		$response = wp_remote_retrieve_body( $request );
		$response = @json_decode( $response, true );

		if ( ! is_array( $response ) ) {
			tribe( 'logger' )->log_error( __( 'Unexpected PayPal Commerce Connect response', 'event-tickets' ), 'tickets-commerce-paypal-commerce' );

			return null;
		}

		return $response;
	}

	/**
	 * Get seller on-boarding details from seller.
	 *
	 * @since 5.1.6
	 *
	 * @param string $accessToken
	 *
	 * @param string $merchantId
	 *
	 * @return array
	 */
	public function getSellerOnBoardingDetailsFromPayPal( $merchantId, $accessToken ) {
		$request = wp_remote_post( $this->connectClient->get_api_url( sprintf( 'paypal-commerce/?mode=%1$s&request=seller-status', $this->payPalClient->mode ) ), [
			'body' => [
				'merchant_id' => $merchantId,
				'token'       => $accessToken,
			],
		] );

		if ( is_wp_error( $request ) ) {
			tribe( 'logger' )->log_error( sprintf(
				// Translators: %s: The error message.
				__( 'PayPal Commerce Connect request error: %s', 'event-tickets' ),
				$request->get_error_message()
			), 'tickets-commerce-paypal-commerce' );

			return null;
		}

		$response = wp_remote_retrieve_body( $request );
		$response = @json_decode( $response, true );

		if ( ! is_array( $response ) ) {
			tribe( 'logger' )->log_error( __( 'Unexpected PayPal Commerce Connect response', 'event-tickets' ), 'tickets-commerce-paypal-commerce' );

			return null;
		}

		return $response;
	}

	/**
	 * Get seller rest API credentials
	 *
	 * @since 5.1.6
	 *
	 * @param string $accessToken
	 *
	 * @return array
	 */
	public function getSellerRestAPICredentials( $accessToken ) {
		$request = wp_remote_post( $this->connectClient->get_api_url( sprintf( 'paypal-commerce/?mode=%1$s&request=seller-credentials', $this->payPalClient->mode ) ), [
			'body' => [
				'token' => $accessToken,
			],
		] );

		if ( is_wp_error( $request ) ) {
			tribe( 'logger' )->log_error( sprintf(
				// Translators: %s: The error message.
				__( 'PayPal Commerce Connect request error: %s', 'event-tickets' ),
				$request->get_error_message()
			), 'tickets-commerce-paypal-commerce' );

			return null;
		}

		$response = wp_remote_retrieve_body( $request );
		$response = @json_decode( $response, true );

		if ( ! is_array( $response ) ) {
			tribe( 'logger' )->log_error( __( 'Unexpected PayPal Commerce Connect response', 'event-tickets' ), 'tickets-commerce-paypal-commerce' );

			return null;
		}

		return $response;
	}
}
