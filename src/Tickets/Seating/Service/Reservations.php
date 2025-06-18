<?php
/**
 * The service component used to exchange reservations infarmation with the service.
 *
 * @since 5.16.0
 *
 * @package TEC\Tickets\Seating\Service;
 */

namespace TEC\Tickets\Seating\Service;

use TEC\Common\StellarWP\DB\DB;
use TEC\Tickets\Seating\Logging;
use TEC\Tickets\Seating\Meta;

/**
 * Class Reservations.
 *
 * @since 5.16.0
 *
 * @package TEC\Tickets\Seating\Service;
 */
class Reservations {
	use Logging;
	use OAuth_Token;

	/**
	 * The URL to the service reservations endpoint.
	 *
	 * @since 5.16.0
	 *
	 * @var string
	 */
	private string $service_fetch_url;

	/**
	 * Reservations constructor.
	 *
	 * @since 5.16.0
	 *
	 * @param string $backend_base_url The base URL of the service from the site backend.
	 */
	public function __construct( string $backend_base_url ) {
		$this->service_fetch_url = rtrim( $backend_base_url, '/' ) . '/api/v1/reservations';
	}

	/**
	 * Prompts the service to cancel the reservations.
	 *
	 * @since 5.16.0
	 *
	 * @param int      $object_id    The object ID to cancel the reservations for.
	 * @param string[] $reservations The reservations to cancel.
	 *
	 * @return bool Whether the reservations were cancelled or not.
	 */
	public function cancel( int $object_id, array $reservations ): bool {
		if ( empty( $reservations ) ) {
			return true;
		}

		$object_uuid = get_post_meta( $object_id, Meta::META_KEY_UUID, true );

		if ( empty( $object_uuid ) ) {
			return false;
		}

		$response = wp_remote_post(
			$this->get_cancel_url(),
			[
				'headers' => [
					'Authorization' => sprintf( 'Bearer %s', $this->get_oauth_token() ),
					'Content-Type'  => 'application/json',
				],
				'body'    => wp_json_encode(
					[
						'eventId' => $object_uuid,
						'ids'     => $reservations,
					]
				),
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
					'body'   => substr( wp_remote_retrieve_body( $response ), 0, 100 ),
				]
			);

			return false;
		}

		return true;
	}

	/**
	 * Returns the URL to the endpoint to cancel the reservations.
	 *
	 * @since 5.16.0
	 *
	 * @return string The URL to the endpoint to cancel the reservations.
	 */
	public function get_cancel_url(): string {
		return $this->service_fetch_url . '/cancel';
	}

	/**
	 * Confirms the reservations.
	 *
	 * @since 5.16.0
	 *
	 * @param int      $object_id    The object ID to confirm the reservations for.
	 * @param string[] $reservations The reservations to confirm.
	 *
	 * @return bool Whether the reservations were confirmed or not.
	 */
	public function confirm( int $object_id, array $reservations ): bool {
		if ( empty( $reservations ) ) {
			return true;
		}

		$object_uuid = get_post_meta( $object_id, Meta::META_KEY_UUID, true );

		if ( empty( $object_uuid ) ) {
			return false;
		}

		$response = wp_remote_post(
			$this->get_confirm_url(),
			[
				'headers' => [
					'Authorization' => sprintf( 'Bearer %s', $this->get_oauth_token() ),
					'Content-Type'  => 'application/json',
				],
				'body'    => wp_json_encode(
					[
						'eventId' => $object_uuid,
						'ids'     => $reservations,
					]
				),
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
					'body'   => substr( wp_remote_retrieve_body( $response ), 0, 100 ),
				]
			);

			return false;
		}

		return true;
	}

	/**
	 * Returns the URL to the endpoint to confirm the reservations.
	 *
	 * @since 5.16.0
	 *
	 * @return string The URL to the endpoint to confirm the reservations.
	 */
	public function get_confirm_url(): string {
		return $this->service_fetch_url . '/confirm';
	}

	/**
	 * Deletes reservations from Attendees.
	 *
	 * @since 5.16.0
	 *
	 * @param string[] $reservation_ids The IDs of the reservations to delete.
	 *
	 * @return int The number of Attendees whose reservations were deleted.
	 *
	 * @throws \Exception If the query fails.
	 */
	public function delete_reservations_from_attendees( array $reservation_ids ): int {
		if ( empty( $reservation_ids ) ) {
			return 0;
		}

		global $wpdb;
		$attendee_post_types = tribe_attendees()->attendee_types();

		if ( empty( $attendee_post_types ) ) {
			return 0;
		}

		$attendee_post_types_list = DB::prepare(
			implode( ', ', array_fill( 0, count( $attendee_post_types ), '%s' ) ),
			...array_values( $attendee_post_types )
		);

		/**
		 * Filters the batch size used to delete reservations from Attendees.
		 * This value should be adjusted to make sure the query will not go over the limits imposed by the database
		 * in its `max_allowed_packet` setting. Furthermore, security and performance plugins might also impose limits
		 * on the size of the query.
		 *
		 * @since 5.16.0
		 *
		 * @param int $batch_size The batch size.
		 */
		$batch_size = (int) apply_filters(
			'tec_tickets_seating_delete_reservations_from_attendees_batch_size',
			100
		);

		$removed = 0;
		do {
			$reservation_ids_batch = array_splice( $reservation_ids, 0, $batch_size );
			$left                  = count( $reservation_ids );
			$reservation_ids_list  = DB::prepare(
				implode( ', ', array_fill( 0, count( $reservation_ids_batch ), '%s' ) ),
				...$reservation_ids_batch
			);

			$affected = DB::get_results(
				DB::prepare(
					"SELECT pm.post_id, pm.meta_id, pm.meta_value FROM %i pm
					JOIN %i p ON p.ID = pm.post_id AND p.post_type IN ({$attendee_post_types_list})
					AND meta_key = %s AND meta_value IN ({$reservation_ids_list})
					ORDER BY pm.meta_id",
					$wpdb->postmeta,
					$wpdb->posts,
					Meta::META_KEY_RESERVATION_ID
				),
				ARRAY_A
			);

			/*
			 * The number of affected Attendees might not match the number of reservation IDs in the batch
			 * generating a potentially wasteful, sparse DELETE query. This should not be the case in most
			 * instances and saves more queries while trying to prepare a dense DELETE query.
			 */

			if ( empty( $affected ) ) {
				continue;
			}

			$reservation_to_attendee_map = array_combine(
				array_column( $affected, 'meta_value' ),
				array_column( $affected, 'post_id' )
			);

			/**
			 * Fires before the reservation meta is removed from Attendees following a removal of that reservation
			 * from the service.
			 *
			 * Note this action will fire multiple times, once for each batch of reservations.
			 *
			 * @since 5.16.0
			 *
			 * @param array<string,int> $reservation_to_attendee_map The map from reservation UUIDs to Attendee IDs.
			 */
			do_action( 'tec_tickets_seating_delete_reservations_from_attendees', $reservation_to_attendee_map );

			$attendee_ids_list = DB::prepare(
				implode( ', ', array_fill( 0, count( $affected ), '%d' ) ),
				...array_column( $affected, 'post_id' )
			);

			// Fetch the meta IDs for meta key _tec_slr_seat_label for the affected Attendees.
			$seat_label_meta_ids = DB::get_col(
				DB::prepare(
					"SELECT meta_id FROM %i WHERE post_id IN ({$attendee_ids_list}) AND meta_key = %s",
					$wpdb->postmeta,
					Meta::META_KEY_ATTENDEE_SEAT_LABEL
				),
			);

			// Generate meta ids list from reservation meta ids + seat label meta ids.
			$meta_ids_list = DB::prepare(
				implode( ', ', array_fill( 0, count( $affected ) + count( $seat_label_meta_ids ), '%d' ) ),
				...array_column( $affected, 'meta_id' ),
				...$seat_label_meta_ids
			);

			$removed_here = (int) DB::query(
				DB::prepare(
					"DELETE FROM %i where meta_id in ({$meta_ids_list})",
					$wpdb->postmeta
				)
			);

			foreach ( array_column( $affected, 'post_id' ) as $attendee_post_id ) {
				clean_post_cache( $attendee_post_id );
			}

			/**
			 * Fires after the reservation meta is removed from Attendees following a removal of that reservation
			 * from the service.
			 *
			 * Note this action will fire multiple times, once for each batch of reservations.
			 *
			 * @since 5.16.0
			 *
			 * @param array<string,int> $reservation_to_attendee_map The map from reservation UUIDs to Attendee IDs.
			 */
			do_action( 'tec_tickets_seating_deleted_reservations_from_attendees', $reservation_to_attendee_map );

			$removed += $removed_here;
			$left     = count( $reservation_ids );
		} while ( $left > 0 );

		return $removed;
	}

	/**
	 * Updates the seat type of all attendees for the given reservations.
	 *
	 * Note: the following code assumes a one-to-one relationship between a reservation and an Attendee.
	 * An Attendee can have zero or one Reservations, a Reservation can be related to zero or one Attendees.
	 *
	 * @since 5.16.0
	 *
	 * @param array<string,string[]> $map A map from seat type IDs to reservation IDs.
	 *
	 * @return int The number of attendees whose seat type was updated.
	 */
	public function update_attendees_seat_type( array $map ): int {
		$total_updated = 0;

		foreach ( $map as $seat_type_id => $reservation_ids ) {
			foreach ( $reservation_ids as $reservation_id ) {
				$attendee_id = tribe_attendees()->where( 'meta_equals', Meta::META_KEY_RESERVATION_ID, $reservation_id )
												->first_id();
				if ( ! $attendee_id ) {
					continue;
				}

				update_post_meta( $attendee_id, Meta::META_KEY_SEAT_TYPE, $seat_type_id );
				clean_post_cache( $attendee_id );
				++$total_updated;
			}
		}

		return $total_updated;
	}
}
