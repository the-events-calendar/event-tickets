<?php
/**
 * ${CARET}
 *
 * @since 5.16.0
 *
 * @package TEC\Controller\Service;
 */

namespace TEC\Tickets\Seating\Service;

use TEC\Tickets\Seating\Logging;
use WP_Error;

/**
 * Class Ephemeral_Token.
 *
 * @since 5.16.0
 *
 * @package TEC\Controller\Service;
 */
class Ephemeral_Token {
	use OAuth_Token;
	use Logging;

	/**
	 * The URL to the ephemeral token endpoint on the service.
	 *
	 * @since 5.16.0
	 *
	 * @var string
	 */
	private string $ephemeral_token_url;

	/**
	 * Ephemeral_Token constructor.
	 *
	 * @since 5.16.0
	 *
	 * @param string $backend_base_url The base URL of the service.
	 */
	public function __construct( string $backend_base_url ) {
		$this->ephemeral_token_url = rtrim( $backend_base_url, '/' ) . '/api/v1/ephemeral-token';
	}

	/**
	 * Fetches an ephemeral token from the service.
	 *
	 * @since 5.16.0
	 *
	 * @param int         $expiration The expiration in seconds. While this value is arbitrary, the service will still
	 *                                return a token whose expiration has been set to 15', 30', 1 hour or 6 hours.
	 * @param string|null $scope The scope of the token to request. Defaults to `visitor` to get a token with the least
	 *                            permissions.
	 *
	 * @return string|WP_Error Either a valid ephemeral token, or a `WP_Error` indicating the failure reason.
	 */
	public function get_ephemeral_token( int $expiration = 900, string $scope = 'visitor' ) {
		/**
		 * Filters the ephemeral token to be used by the service before the default logic fetches one from the service.
		 *
		 * @since 5.16.0
		 *
		 * @param string|null $ephemeral_token The ephemeral token to be used by the service. If not `null`, the default
		 *                                     logic will not be used.
		 * @param int         $expiration      The expiration in seconds. While this value is arbitrary, the service will
		 *                                     still return a token whose expiration has been set to 15', 30', 1 hour or
		 *                                     6 hours.
		 * @param string      $scope           The scope of the token to request. Defaults to `visitor` to get a token with
		 *                                     the least permissions.
		 */
		$token = apply_filters( 'tec_tickets_seating_ephemeral_token', null, $expiration, $scope );

		if ( null !== $token ) {
			return $token;
		}

		/**
		 * Filters the site URL used to obtain an ephemeral token from the service.
		 *
		 * @since 5.16.0
		 *
		 * @param string $site_url The site URL, defaulting to the home URL.
		 */
		$site_url = apply_filters( 'tec_tickets_seating_ephemeral_token_site_url', home_url() );

		$response = wp_remote_post(
			add_query_arg(
				[
					'site'       => urlencode_deep( $site_url ),
					'expires_in' => $expiration * 1000, // In milliseconds.
					'scope'      => $scope,
				],
				$this->get_ephemeral_token_url()
			),
			[
				'headers' => [
					'Accept'        => 'application/json',
					'Authorization' => sprintf( 'Bearer %s', $this->get_oauth_token() ),
				],
			]
		);

		$code = wp_remote_retrieve_response_code( $response );

		if ( 200 !== $code ) {
			$error_code = $response instanceof WP_Error ? $response->get_error_code() : $code;
			$error      = $response instanceof WP_Error ? $response->get_error_message() : 'n/a';

			$this->log_error(
				'Fetching ephemeral token from service.',
				[
					'source' => __METHOD__,
					'code'   => $error_code,
					'error'  => $error,
				]
			);

			return new \WP_Error(
				'ephemeral_token_request_failed',
				sprintf(
					// translators: 1: failure reason.
					__( 'Ephemeral token request failed. Your site cannot connect to the Seating Builder service (%s).', 'event-tickets' ),
					$error
				),
				[
					'code'  => $error_code,
					'error' => $error,
				]
			);
		}

		$body = wp_remote_retrieve_body( $response );

		if ( ! is_string( $body ) ) {
			$this->log_error(
				'Ephemeral token response from service is empty.',
				[
					'source' => __METHOD__,
				]
			);

			return new \WP_Error(
				'ephemeral_token_response_empty',
				__( 'Ephemeral token response from service is empty.', 'event-tickets' )
			);
		}

		$json = json_decode( $body, true );


		if ( ! ( is_array( $json ) && isset( $json['data'] ) && is_array( $json['data'] ) && isset( $json['data']['token'] ) ) ) {
			$this->log_error(
				'Malformed ephemeral token response body from service.',
				[
					'source' => __METHOD__,
					'body'   => substr( $body, 0, 100 ),
				]
			);

			return new \WP_Error(
				'ephemeral_token_response_invalid',
				__( 'Ephemeral token response from service is invalid.', 'event-tickets' ),
				[ 'body' => $body ]
			);
		}

		return $json['data']['token'];
	}

	/**
	 * Returns the URL to the ephemeral token endpoint.
	 *
	 * @since 5.16.0
	 *
	 * @return string The URL to the ephemeral token endpoint.
	 */
	public function get_ephemeral_token_url(): string {
		return $this->ephemeral_token_url;
	}
}
