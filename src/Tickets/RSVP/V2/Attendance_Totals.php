<?php
/**
 * V2 Attendance Totals class for RSVP.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */

namespace TEC\Tickets\RSVP\V2;

use TEC\Tickets\RSVP\V2\Repositories\Attendee_Repository;

/**
 * Class Attendance_Totals
 *
 * Calculates attendance totals for RSVP tickets in V2 implementation.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */
class Attendance_Totals {

	/**
	 * Get the count of going attendees for an event.
	 *
	 * @since TBD
	 *
	 * @param int $event_id The event ID.
	 *
	 * @return int The count of going attendees.
	 */
	public function get_going_count( int $event_id ): int {
		$repo = new Attendee_Repository();

		return $repo->by( 'event', $event_id )->by( 'going', true )->count();
	}

	/**
	 * Get the count of not going attendees for an event.
	 *
	 * @since TBD
	 *
	 * @param int $event_id The event ID.
	 *
	 * @return int The count of not going attendees.
	 */
	public function get_not_going_count( int $event_id ): int {
		$repo = new Attendee_Repository();

		return $repo->by( 'event', $event_id )->by( 'not_going', true )->count();
	}

	/**
	 * Get the total count of attendees (going and not going) for an event.
	 *
	 * @since TBD
	 *
	 * @param int $event_id The event ID.
	 *
	 * @return int The total count of attendees.
	 */
	public function get_total_count( int $event_id ): int {
		$repo = new Attendee_Repository();

		return $repo->by( 'event', $event_id )->count();
	}
}
