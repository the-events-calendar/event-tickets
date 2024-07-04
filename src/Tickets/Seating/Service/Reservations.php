<?php
/**
 * The service component used to exchange reservations infarmation with the service.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Seating\Service;
 */

namespace TEC\Tickets\Seating\Service;

use TEC\Tickets\Seating\Logging;
use TEC\Tickets\Seating\Meta;

/**
 * Class Reservations.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Seating\Service;
 */
class Reservations {
	use Logging;
	use OAuth_Token;

	/**
	 * The URL to the service reservations endpoint.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	private string $service_fetch_url;

	/**
	 * Reservations constructor.
	 *
	 * @since TBD
	 *
	 * @param string $backend_base_url The base URL of the service from the site backend.
	 */
	public function __construct( string $backend_base_url ) {
		$this->service_fetch_url = rtrim( $backend_base_url, '/' ) . '/api/v1/reservations';
	}

	/**
	 * Prompts the service to cancel the reservations.
	 *
	 * @since TBD
	 *
	 * @param int $object_id The object ID to cancel the reservations for.
	 * @param string[] $reservations The reservations to cancel.
	 *
	 * @return bool Whether the reservations were cancelled or not.
	 */
	public function cancel( int $object_id, array $reservations ): bool {
		if ( empty( $reservations ) ) {
			return true;
		}

		$object_uuid = get_post_meta( $object_id, Meta::META_KEY_UUID, true );

		if( empty( $object_uuid ) ) {
			return false;
		}

		$response = wp_remote_post(
			$this->service_fetch_url . '/cancel',
			[
				'headers' => [
					'Authorization' => sprintf( 'Bearer %s', $this->get_oauth_token() ),
				],
				'body' => wp_json_encode( [
					'eventId' => $object_uuid,
					'ids' => $reservations
				] ),
			]
		);

		if ( is_wp_error( $response ) ) {
			$this->log_error(
				'Cancelling the reservations.',
				[
					'source' => __METHOD__,
					'code'   => $response->get_error_code(),
					'error'  => $response->get_error_message(),
				]
			);

			return false;
		}

		$code = wp_remote_retrieve_response_code( $response );

		if ( 200 !== $code ) {
			$this->log_error(
				'Cancelling the reservations.',
				[
					'source' => __METHOD__,
					'code'   => $code,
				]
			);

			return false;
		}

		$decoded = json_decode( wp_remote_retrieve_body( $response ), true, 512 );

		if ( ! (
			$decoded
			&& is_array( $decoded )
			&& ! empty( $decoded['success'] )
		) ) {
			$this->log_error(
				'Cancelling the reservations.',
				[
					'source' => __METHOD__,
					'body'   => substr( wp_remote_retrieve_body( $response ), 0, 100 )
				]
			);

			return false;
		}

		return true;
	}

	/**
	 * Confirms the reservations.
	 *
	 * @since TBD
	 *
	 * @param string[] $reservations The reservations to confirm.
	 *
	 * @return bool Whether the reservations were confirmed or not.
	 */
	public function confirm( array $reservations ): bool {
		if ( empty( $reservations ) ) {
			return true;
		}

		$response = wp_remote_post(
			$this->service_fetch_url . '/confirm',
			[
				'headers' => [
					'Authorization' => sprintf( 'Bearer %s', $this->get_oauth_token() ),
				],
				'body'    => wp_json_encode( $reservations ),
			]
		);

		if ( is_wp_error( $response ) ) {
			$this->log_error(
				'Confirming the reservations.',
				[
					'source' => __METHOD__,
					'code'   => $response->get_error_code(),
					'error'  => $response->get_error_message(),
				]
			);

			return false;
		}

		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			$this->log_error(
				'Confirming the reservations.',
				[
					'source' => __METHOD__,
					'code'   => wp_remote_retrieve_response_code( $response ),
				]
			);

			return false;
		}

		$decoded = json_decode( wp_remote_retrieve_body( $response ), true, 512 );

		if ( ! (
			$decoded
			&& is_array( $decoded )
			&& ! empty( $decoded['success'] )
		) ) {
			$this->log_error(
				'Confirming the reservations.',
				[
					'source' => __METHOD__,
					'body'   => substr( wp_remote_retrieve_body( $response ), 0, 100 )
				]
			);

			return false;
		}

		return true;
	}
}