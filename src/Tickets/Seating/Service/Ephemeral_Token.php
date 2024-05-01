<?php
/**
 * ${CARET}
 *
 * @since TBD
 *
 * @package TEC\Controller\Service;
 */

namespace TEC\Tickets\Seating\Service;

use TEC\Tickets\Seating\Logging;
use WP_Error;

/**
 * Class Ephemeral_Token.
 *
 * @since TBD
 *
 * @package TEC\Controller\Service;
 */
class Ephemeral_Token {
	use oAuth_Token;
	use Logging;

	/**
	 * The URL to the ephemeral token endpoint on the service.
	 *
	 * @since TBD
	 *
	 * @var
	 */
	private string $ephemeral_token_url;

	/**
	 * Ephemeral_Token constructor.
	 *
	 * since TBD
	 *
	 * @param string $backend_base_url
	 */
	public function __construct( string $backend_base_url ) {
		$this->ephemeral_token_url = rtrim( $backend_base_url, '/' ) . '/api/v1/ephemeral-token';
	}

	/**
	 * Fetches an ephemeral token from the service.
	 *
	 * @since TBD
	 *
	 * @param int $expiration The expiration in seconds. While this value is arbitrary, the service will still
	 *                        return a token whose expiration has been set to 15', 30', 1 hour or 6 hours.
	 *
	 * @return string|WP_Error Either a valid ephemeral token, or a `WP_Error` indicating the failure reason.
	 */
	public function get_ephemeral_token( int $expiration = 900 ) {
		/**
		 * Filters the site URL used to obtain an ephemeral token from the service.
		 *
		 * @since TBD
		 *
		 * @param string $site_url The site URL, defaulting to the home URL.
		 */
		$site_url = apply_filters( 'tec_events_assigned_seating_ephemeral_token_site_url', home_url() );

		$response = wp_remote_post( add_query_arg( [
			'site'       => urlencode_deep( $site_url ),
			'expires_in' => $expiration * 1000, // In milliseconds.
		], $this->get_ephemeral_token_url() ),
			[
				'headers' => [
					'Accept'        => 'application/json',
					'Authorization' => sprintf( 'Bearer %s', $this->get_oauth_token() ),
				]
			]
		);

		$code = wp_remote_retrieve_response_code( $response );

		if ( 200 !== $code ) {
			$this->log_error(
				'Fetching ephemeral token from service.',
				[
					'source' => __METHOD__,
					'code'   => $code,
				]
			);

			return new \WP_Error(
				'ephemeral_token_request_failed',
				sprintf(
					// translators: 1: HTTP status code
					__( 'Ephemeral token request failed (%d).', 'events-assigned-seating' ),
					$code
				),
				[ 'code' => $code ]
			);
		}

		$body = wp_remote_retrieve_body( $response );

		if ( ! is_string( $body ) ) {
			$this->log_error(
				'Ephemeral token response from service is empty.',
				[
					'source' => __METHOD__
				]
			);

			return new \WP_Error(
				'ephemeral_token_response_empty',
				__( 'Ephemeral token response from service is empty.', 'events-assigned-seating' )
			);
		}

		$json = json_decode( $body, true );


		if ( ! ( is_array( $json ) && isset( $json['data'] ) && is_array( $json['data'] ) && isset( $json['data']['token'] ) ) ) {
			$this->log_error(
				'Malformed ephemeral token response body from service.',
				[
					'source' => __METHOD__,
					'body'   => substr( $body, 0, 100 )
				]
			);

			return new \WP_Error(
				'ephemeral_token_response_invalid',
				__( 'Ephemeral token response from service is invalid.', 'events-assigned-seating' ),
				[ 'body' => $body ]
			);
		}

		return $json['data']['token'];
	}

	/**
	 * Returns the URL to the ephemeral token endpoint.
	 *
	 * @since TBD
	 *
	 * @return string The URL to the ephemeral token endpoint.
	 */
	public function get_ephemeral_token_url() {
		return $this->ephemeral_token_url;
	}
}