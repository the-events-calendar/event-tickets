<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal;

use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Core\SandboxEnvironment;

use TEC\Test\functions\template_tags\metaTest;
use Tribe__Utils__Array as Arr;

/**
 * Class Client
 *
 * @since   5.1.6
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
	 * Safely checks if we have an access token to be used.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_access_token() {
		return tribe( Merchant::class )->get_access_token();
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
	 * Get REST API endpoint URL for requests.
	 *
	 * @since TBD
	 *
	 *
	 * @param string $endpoint   The endpoint path.
	 * @param array  $query_args Query args appended to the URL.
	 *
	 * @return string The API URL.
	 *
	 */
	public function get_api_url( $endpoint, array $query_args = [] ) {
		$base_url = $this->get_environment()->baseUrl();

		return add_query_arg( $query_args, "{$base_url}/{$endpoint}" );
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


	/**
	 * Send a POST request to WhoDat inside of the PayPal connection path.
	 *
	 * @since TBD
	 *
	 * @param string $endpoint
	 * @param array  $query_args
	 * @param array  $request_arguments
	 *
	 * @return array|null
	 */
	public function post( $endpoint, array $query_args = [], array $request_arguments = [] ) {
		$url = $this->get_api_url( $endpoint, $query_args );

		$default_arguments = [
			'headers' => [
				'Accept'        => 'application/json',
				'Authorization' => sprintf( 'Bearer %1$s', $this->get_access_token() ),
				'Content-Type'  => 'application/json',
			],
			'body'    => [],
		];
		$request_arguments = array_merge_recursive( $default_arguments, $request_arguments );
		$response          = wp_remote_post( $url, $request_arguments );

		if ( is_wp_error( $response ) ) {
			tribe( 'logger' )->log_error( sprintf(
				'[%s] PayPal request error: %s',
				$url,
				$response->get_error_message()
			), 'tickets-commerce-paypal' );

			return null;
		}

		$response_code = wp_remote_retrieve_response_code( $response );

		// When we receive an error code we return the whole response.
		if ( ! in_array( $response_code, [ 200, 201, 202, 204 ], true ) ) {
			return $response;
		}

		$response = wp_remote_retrieve_body( $response );
		$response = @json_decode( $response, true );

		if ( ! is_array( $response ) ) {
			tribe( 'logger' )->log_error( sprintf( '[%s] Unexpected PayPal response', $url ), 'tickets-commerce-paypal' );

			return null;
		}

		return $response;
	}

	/**
	 * Retrieves an Access Token for the Client ID and Secret.
	 *
	 * @since TBD
	 *
	 * @param string $client_id     The Client ID.
	 * @param string $client_secret The Client Secret.
	 *
	 * @return array|null The token details response or null if there was a problem.
	 */
	public function get_access_token_from_client_credentials( $client_id, $client_secret ) {
		$auth       = base64_encode( "$client_id:$client_secret" );
		$query_args = [];

		$args = [
			'headers' => [
				'Authorization' => sprintf( 'Basic %1$s', $auth ),
				'Content-Type'  => 'application/x-www-form-urlencoded',
			],
			'body'    => [
				'grant_type' => 'client_credentials',
			],
		];

		return $this->post( 'v1/oauth2/token', $query_args, $args );
	}

	/**
	 * Retrieves an Access Token from the authorization code.
	 *
	 * @since TBD
	 *
	 * @param string $shared_id Shared ID for merchant.
	 * @param string $auth_code Authorization code from on boarding.
	 * @param string $nonce     Seller nonce from on boarding.
	 *
	 * @return array|null The token details response or null if there was a problem.
	 */
	public function get_access_token_from_authorization_code( $shared_id, $auth_code, $nonce ) {
		$auth       = base64_encode( $shared_id );
		$query_args = [];

		$args = [
			'headers' => [
				'Authorization' => sprintf( 'Basic %1$s', $auth ),
				'Content-Type'  => 'application/x-www-form-urlencoded',
			],
			'body'    => [
				'grant_type'    => 'authorization_code',
				'code'          => $auth_code,
				'code_verifier' => $nonce,
			],
		];

		return $this->post( 'v1/oauth2/token', $query_args, $args );
	}

	/**
	 * Retrieves a Client Token from the stored Access Token.
	 *
	 * @link  https://developer.paypal.com/docs/business/checkout/advanced-card-payments/
	 *
	 * @since TBD
	 *
	 * @return array|null The client token details response or null if there was a problem.
	 */
	public function get_client_token() {
		$query_args = [];
		$args       = [
			'headers' => [
				'Accept'          => 'application/json',
				'Accept-Language' => 'en_US',
				'Authorization'   => sprintf( 'Bearer %1$s', $this->get_access_token() ),
				'Content-Type'    => 'application/json',
			],
			'body'    => [],
		];

		return $this->post( 'v1/identity/generate-token', $query_args, $args );
	}

	/**
	 * Based on a Purchase Unit creates a PayPal order.
	 *
	 * @link https://developer.paypal.com/docs/api/orders/v2/#orders_create
	 * @link https://developer.paypal.com/docs/api/orders/v2/#definition-purchase_unit_request
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed>|array<array> $units              {
	 *                                                             Purchase unit used to setup the order in PayPal.
	 *
	 * @type string                            $reference_id       Reference ID to PayPal.
	 * @type string                            $description        Description of this Purchase Unit.
	 * @type string                            $value              Value to be payed.
	 * @type string                            $currency           Which currency.
	 * @type string                            $merchant_id        Merchant ID.
	 * @type string                            $merchant_paypal_id PayPal Merchant ID.
	 * @type string                            $first_name         Payee First Name.
	 * @type string                            $last_name          Payee Last Name.
	 * @type string                            $email              Payee email.
	 * @type string                            $disbursement_mode  (optional) By default 'INSTANT'.
	 * @type string                            $payer_id           (optional) PayPal Payer ID
	 * @type string                            $tax_id             (optional) Tax ID for this purchase Unit.
	 * @type string                            $tax_id_type        (optional) Tax ID for this purchase Unit.
	 *
	 *                     }
	 * @return array|null
	 */
	public function create_order( array $units = [] ) {
		$query_args = [];
		$body       = [
			'intent'              => 'CAPTURE',
			'purchase_units'      => [],
			'application_context' => [
				'shipping_preference' => 'NO_SHIPPING',
				'user_action'         => 'PAY_NOW',
			],
		];

		// Determine if this set of units was just a single unit before looping.
		if ( ! empty( $units['reference_id'] ) ) {
			$units = [ $units ];
		}

		foreach ( $units as $unit ) {
			/**
			 * @link https://developer.paypal.com/docs/api/orders/v2/#definition-payer
			 */
			$purchase_unit = [
				'reference_id'        => Arr::get( $unit, 'reference_id' ),
				'description'         => Arr::get( $unit, 'description' ),
				'amount'              => [
					'value'         => Arr::get( $unit, 'value' ),
					'currency_code' => Arr::get( $unit, 'currency' ),
				],
				'payee'               => [
					'email_address' => Arr::get( $unit, 'merchant_id', tribe( Merchant::class )->get_merchant_id() ),
					'merchant_id'   => Arr::get( $unit, 'merchant_paypal_id', tribe( Merchant::class )->get_merchant_id_in_paypal() ),
				],
				'payer'               => [
					'name'          => [
						'given_name' => Arr::get( $unit, 'first_name' ),
						'surname'    => Arr::get( $unit, 'last_name' ),
					],
					'email_address' => Arr::get( $unit, 'email' ),

				],
				'payment_instruction' => [
					'disbursement_mode' => Arr::get( $unit, 'disbursement_mode', 'INSTANT' ),
				],
			];

			if ( ! empty( $unit['payer_id'] ) ) {
				$purchase_unit['payer']['payer_id'] = Arr::get( $unit, 'payer_id' );
			}
			if ( ! empty( $unit['tax_id'] ) ) {
				$purchase_unit['payer']['tax_info']['tax_id'] = Arr::get( $unit, 'tax_id' );
			}
			if ( ! empty( $unit['tax_id_type'] ) ) {
				$purchase_unit['payer']['tax_info']['tax_id_type'] = Arr::get( $unit, 'tax_id_type' );
			}

			/**
			 * @todo We should have some sort of Purchase Unit validation here.
			 */

			$body['purchase_units'][] = $purchase_unit;
		}

		$args = [
			'headers' => [
				'PayPal-Partner-Attribution-Id' => Gateway::ATTRIBUTION_ID,
				'Prefer'                        => 'return=representation',
			],
			'body'    => $body,
		];

		$response = $this->post( '/v2/checkout/orders', $query_args, $args );

		return $response;
	}

}
