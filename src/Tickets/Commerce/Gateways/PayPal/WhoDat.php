<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal;

/**
 * Class Connect_Client
 *
 * @since   5.1.6
 *
 * @package TEC\Tickets\Commerce\Gateways\PayPal
 */
class WhoDat {

	/**
	 * The API URL.
	 *
	 * @since 5.1.6
	 *
	 * @var string
	 */
	protected $api_url = 'https://whodat.theeventscalendar.com/tickets/paypal/connect';

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
	 */
	public function get_api_url( $endpoint, array $query_args = [] ) {
		return add_query_arg( $query_args, "{$this->api_url}/{$endpoint}" );
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
			'body'      => [],

			// @todo Remove this when SSL is fixed.
			'sslverify' => false,
		];
		$request_arguments = array_merge_recursive( $default_arguments, $request_arguments );
		$response          = wp_remote_post( $url, $request_arguments );

		if ( is_wp_error( $response ) ) {
			tribe( 'logger' )->log_error( sprintf(
				'[%s] WhoDat request error: %s',
				$url,
				$response->get_error_message()
			), 'whodat-connection' );

			return null;
		}

		$response = wp_remote_retrieve_body( $response );
		$response = @json_decode( $response, true );

		if ( ! is_array( $response ) ) {
			tribe( 'logger' )->log_error( sprintf( '[%s] Unexpected WhoDat response', $url ), 'whodat-connection' );

			return null;
		}

		return $response;
	}

	/**
	 * Retrieves a Partner Link for on-boarding
	 *
	 * @param $return_url
	 * @param $country
	 *
	 * @return array|null
	 */
	public function get_seller_partner_link( $return_url, $country ) {
		$query_args = [
			'mode' => tribe( Merchant::class )->get_mode(),
			'request' => 'partner-link',
		];

		$args = [
			'body' => [
				'return_url'   => $return_url,
				'country_code' => $country,
			]
		];

		return $this->post( 'paypal-commerce', $query_args, $args );
	}

	/**
	 * Get seller on-boarding details from seller.
	 *
	 * @since 5.1.6
	 *
	 * @param string $access_token
	 * @param string $merchant_id
	 *
	 * @return array|null
	 */
	public function get_seller_on_boarding_details_from_paypal( $merchant_id, $access_token ) {
		$query_args = [
			'mode' => tribe( Merchant::class )->get_mode(),
			'request' => 'seller-status',
		];

		$args = [
			'body' => [
				'merchant_id' => $merchant_id,
				'token' => $access_token,
			]
		];

		return $this->post( 'paypal-commerce', $query_args, $args );
	}

	/**
	 * Get seller rest API credentials
	 *
	 * @since 5.1.6
	 *
	 * @param string $access_token
	 *
	 * @return array|null
	 */
	public function get_seller_rest_api_credentials( $access_token ) {
		$query_args = [
			'mode' => tribe( Merchant::class )->get_mode(),
			'request' => 'seller-credentials',
		];

		$args = [
			'body' => [
				'token' => $access_token,
			]
		];

		return $this->post( 'paypal-commerce', $query_args, $args );
	}

}
