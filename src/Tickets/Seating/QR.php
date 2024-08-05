<?php
/**
 * Handles the integration of the Seating feature with the QR code functionality.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Seating;
 */

namespace TEC\Tickets\Seating;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;

/**
 * Class QR.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Seating;
 */
class QR extends Controller_Contract {
	/**
	 * Binds and sets up implementations, subscribes to WordPress hooks and binds implementations.
	 *
	 * @since TBD
	 */
	protected function do_register(): void {
		add_filter( 'tec_tickets_qr_checkin_attendee_data', [ $this, 'inject_qr_data' ], 10, 2 );
	}

	/**
	 * Unregisters the controller by unsubscribing from WordPress hooks.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_filter( 'tec_tickets_qr_checkin_attendee_data', [ $this, 'inject_qr_data' ], 10, 2 );
	}

	/**
	 * Injects ASC data into the data returned when a QR code is scanned.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $attendee_data The data returned when a QR code is scanned.
	 * @param int                 $attendee_id   The ID of the attendee.
	 *
	 * @return array<string,mixed> The data returned when a QR code is scanned, filtered to include ASC data if applicable.
	 */
	public function inject_qr_data( $attendee_data, $attendee_id ) {
		if ( ! (
			is_array( $attendee_data )
			&& is_numeric( $attendee_id )
			&& (int) $attendee_id == $attendee_id
			&& isset( $attendee_data['ticket_id'] )
		) ) {
			return $attendee_data;
		}

		// Let's check the ticket of this Attendee is an ASC one.
		$ticket_id             = $attendee_data['ticket_id'];
		$uses_assigned_seating = get_post_meta( $ticket_id, Meta::META_KEY_ENABLED, true );

		if ( ! $uses_assigned_seating ) {
			return $attendee_data;
		}

		$attendee_data['asc_ticket'] = true;
		$attendee_data['seat_label'] = ( (string) get_post_meta(
			$attendee_id,
			Meta::META_KEY_ATTENDEE_SEAT_LABEL,
			true 
		)
		);

		return $attendee_data;
	}
}
