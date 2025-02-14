<?php
/**
 * Modifies REST API responses as required by the Seating feature.
 *
 * @since 5.18.1
 *
 * @package TEC\Tickets\Seating;
 */

namespace TEC\Tickets\Seating;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;

/**
 * Class REST.
 *
 * @since 5.18.1
 *
 * @package TEC\Tickets\Seating;
 */
class REST extends Controller_Contract {
	/**
	 * Subscribes the controller to relevant hooks, binds implementations.
	 *
	 * @since 5.18.1
	 *
	 * @return void
	 */
	protected function do_register(): void {
		add_filter( 'tec_tickets_rest_attendee_archive_data', [ $this, 'inject_attendee_data' ] );
	}

	/**
	 * Unsubscribes the controller from all registered hooks.
	 *
	 * @since 5.18.1
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_filter( 'tec_tickets_rest_attendee_archive_data', [ $this, 'inject_attendee_data' ] );
	}

	/**
	 * Injects ASC related data in the Attendee REST response.
	 *
	 * @since 5.18.1
	 *
	 * @param array<string,mixed> $data The Attendee archive REST API response data.
	 *
	 * @return array<string,mixed> The Attendee archive REST API response data modified to include ASC related data.
	 */
	public function inject_attendee_data( $data ) {
		if ( ! is_array( $data ) ) {
			return $data;
		}

		if ( ! tribe( 'tickets.rest-v1.main' )->request_has_manage_access() ) {
			return $data;
		}

		$attendees = $data['attendees'] ?? [];

		if ( ! is_array( $attendees ) ) {
			return $data;
		}

		array_walk(
			$attendees,
			function ( &$attendee ) {
				if ( ! ( is_array( $attendee ) && isset( $attendee['id'], $attendee['ticket_id'] ) ) ) {
					return;
				}

				// Let's check the ticket of this Attendee is an ASC one.
				$ticket_id             = $attendee['ticket_id'];
				$uses_assigned_seating = get_post_meta( $ticket_id, Meta::META_KEY_ENABLED, true );

				if ( ! $uses_assigned_seating ) {
					return;
				}

				$seat_label             = get_post_meta( $attendee['id'], Meta::META_KEY_ATTENDEE_SEAT_LABEL, true );
				$attendee['asc_ticket'] = true;
				$attendee['seat_label'] = $seat_label ?: '';
			}
		);

		$data['attendees'] = $attendees;

		return $data;
	}
}
