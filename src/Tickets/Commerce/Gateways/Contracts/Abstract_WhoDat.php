<?php
/**
 * WhoDat Connection Contract.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Contracts
 */

// phpcs:disable StellarWP.Classes.ValidClassName.NotSnakeCase
namespace TEC\Tickets\Commerce\Gateways\Contracts;

use Tribe__Utils__Array as Arr;

/**
 * Abstract class to handle WhoDat connections
 *
 * @since   5.3.0
 *
 * @package TEC\Tickets\Commerce\Gateways\PayPal
 */
abstract class Abstract_WhoDat implements WhoDat_Interface {
	/**
	 * The API endpoint.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected const API_ENDPOINT = '';

	/**
	 * WhoDat URL, used to authenticate accounts with gateway payment providers
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected const API_BASE_URL = 'https://whodat.theeventscalendar.com/commerce/v1';

	/**
	 * @inheritDoc
	 */
	public function get_gateway_endpoint(): string {
		return static::API_ENDPOINT;
	}

	/**
	 * @inheritDoc
	 */
	public function get_api_base_url(): string {
		if ( defined( 'TEC_TC_WHODAT_DEV_URL' ) && TEC_TC_WHODAT_DEV_URL ) {
			return untrailingslashit( TEC_TC_WHODAT_DEV_URL );
		}

		return untrailingslashit( static::API_BASE_URL );
	}

	/**
	 * @inheritDoc
	 */
	public function get_api_url( $endpoint, array $query_args = [] ): string {
		return add_query_arg( $query_args, "{$this->get_api_base_url()}/{$this->get_gateway_endpoint()}/{$endpoint}" );
	}

	/**
	 * @inheritDoc
	 */
	public function get( $endpoint, array $query_args ) {
		$url = $this->get_api_url( $endpoint, $query_args );

		$request = wp_remote_get( $url );

		if ( is_wp_error( $request ) ) {
			$this->log_error( 'WhoDat request error:', $request->get_error_message(), $url );

			return null;
		}

		$body = wp_remote_retrieve_body( $request );
		$body = json_decode( $body, true );

		return $body;
	}

	/**
	 * @inheritDoc
	 */
	public function post( $endpoint, array $query_args = [], array $request_arguments = [] ) {
		$url = $this->get_api_url( $endpoint, $query_args );

		$default_arguments = [
			'body'    => [],
			'headers' => [],
		];

		foreach ( $default_arguments as $key => $default_argument ) {
			$request_arguments[ $key ] = array_merge( $default_argument, Arr::get( $request_arguments, $key, [] ) );
		}

		// Check if headers indicate JSON content type
		$is_json = false;
		if ( isset( $request_arguments['headers']['Content-Type'] ) && false !== strpos( $request_arguments['headers']['Content-Type'], 'application/json' ) ) {
			$is_json = true;
		} elseif ( isset( $request_arguments['headers']['content-type'] ) && false !== strpos( $request_arguments['headers']['content-type'], 'application/json' ) ) {
			$is_json = true;
		}

		// If JSON content type, convert body to JSON
		if ( $is_json && ! empty( $request_arguments['body'] ) && is_array( $request_arguments['body'] ) ) {
			$request_arguments['body'] = wp_json_encode( $request_arguments['body'] );
		}

		$request_arguments = array_filter( $request_arguments );
		$response          = wp_remote_post( $url, $request_arguments );

		if ( is_wp_error( $response ) ) {
			$this->log_error( 'WhoDat request error:', $response->get_error_message(), $url );
			return null;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $status_code ) {
			return null;
		}

		$body = wp_remote_retrieve_body( $response );
		$body = json_decode( $body, true );

		if ( ! is_array( $body ) ) {
			$this->log_error( 'WhoDat unexpected response:', $body, $url );
			$this->log_error( 'Response:', print_r( $response, true ), '--->' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r

			return null;
		}

		return $body;
	}

	/**
	 * @inheritDoc
	 */
	public function log_error( $type, $message, $url ) {
		$log = sprintf(
			'[%s] %s %s',
			$url,
			$type,
			$message
		);
		do_action( 'tribe_log', 'error', __CLASS__, [ $log ] );
	}
}
