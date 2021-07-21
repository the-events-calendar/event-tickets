<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal\Repositories;

use TEC\Tickets\Commerce\Gateways\PayPal\Connect_Client;
use TEC\Tickets\Commerce\Gateways\PayPal\PayPal_Client;

/**
 * Class PayPal_Auth
 *
 * @since 5.1.6
 * @package TEC\Tickets\Commerce\Gateways\PayPal\Repositories
 */
class PayPal_Auth {

	/**
	 * @since 5.1.6
	 *
	 * @var PayPal_Client
	 */
	private $paypal_client;

	/**
	 * @since 5.1.6
	 *
	 * @var Connect_Client
	 */
	private $connect_client;

	/**
	 * PayPalAuth constructor.
	 *
	 * @since 5.1.6
	 *
	 * @param PayPal_Client  $paypal_client
	 * @param Connect_Client $connect_client
	 */
	public function __construct( PayPal_Client $paypal_client, Connect_Client $connect_client ) {
		$this->paypal_client  = $paypal_client;
		$this->connect_client = $connect_client;
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
	public function get_token_from_client_credentials( $client_id, $client_secret ) {
		$auth = base64_encode( "$client_id:$client_secret" );

		$request = wp_remote_post( $this->paypal_client->get_api_url( 'v1/oauth2/token' ), [
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
	 * @param string $shared_id Shared ID for merchant.
	 * @param string $auth_code Authorization code from onboarding.
	 * @param string $nonce     Seller nonce from onboarding.
	 *
	 * @return array|null The token details response or null if there was a problem.
	 */
	public function get_token_from_authorization_code( $shared_id, $auth_code, $nonce ) {
		$request = wp_remote_post( $this->paypal_client->get_api_url( 'v1/oauth2/token' ), [
			'headers' => [
				'Authorization' => sprintf( 'Basic %1$s', base64_encode( $shared_id ) ),
				'Content-Type'  => 'application/x-www-form-urlencoded',
			],
			'body'    => [
				'grant_type'    => 'authorization_code',
				'code'          => $auth_code,
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
	public function get_seller_partner_link( $return_url, $country ) {
		$request = wp_remote_post( sprintf( $this->connect_client->get_api_url( 'paypal-commerce/?mode=%1$s&request=partner-link' ), $this->paypal_client->mode ), [
			'body'      => [
				'return_url'   => $return_url,
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
	 * @param string $access_token
	 *
	 * @param string $merchant_id
	 *
	 * @return array
	 */
	public function get_seller_on_boarding_details_from_paypal( $merchant_id, $access_token ) {
		$request = wp_remote_post( $this->connect_client->get_api_url( sprintf( 'paypal-commerce/?mode=%1$s&request=seller-status', $this->paypal_client->mode ) ), [
			'body' => [
				'merchant_id' => $merchant_id,
				'token'       => $access_token,
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
	public function get_seller_rest_api_credentials( $access_token ) {
		$request = wp_remote_post( $this->connect_client->get_api_url( sprintf( 'paypal-commerce/?mode=%1$s&request=seller-credentials', $this->paypal_client->mode ) ), [
			'body' => [
				'token' => $access_token,
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
