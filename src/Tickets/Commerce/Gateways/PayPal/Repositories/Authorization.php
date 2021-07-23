<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal\Repositories;

use TEC\Tickets\Commerce\Gateways\PayPal\WhoDat;
use TEC\Tickets\Commerce\Gateways\PayPal\Client;

/**
 * Class Authorization
 *
 * @since 5.1.6
 * @package TEC\Tickets\Commerce\Gateways\PayPal\Repositories
 */
class Authorization {

	/**
	 * @since 5.1.6
	 *
	 * @var Client
	 */
	private $paypal_client;

	/**
	 * @since 5.1.6
	 *
	 * @var WhoDat
	 */
	private $connect_client;

	/**
	 * Authorization constructor.
	 *
	 * @since 5.1.6
	 *
	 * @param Client $paypal_client
	 * @param WhoDat $connect_client
	 */
	public function __construct( Client $paypal_client, WhoDat $connect_client ) {
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

}
