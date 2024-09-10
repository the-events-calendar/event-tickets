<?php
/**
 * Momoize Tickets class is a helper class for the Tickets List Table to reduce the number of queries.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Admin\Commerce
 */

namespace TEC\Tickets\Commerce;

/**
 * Class Memoize_Tickets
 *
 * @since TBD
 *
 * @package TEC\Tickets\Admin\Commerce
 */
class Memoize_Tickets {
	/**
	 * The memoized tickets.
	 *
	 * @var array
	 */
	protected $tickets = [];

	/**
	 * The memoized ticket ids.
	 *
	 * @var array
	 */
	protected $attendees_by_ticket_id = [];

	/**
	 * Memoization for attendees by ticket and status.
	 *
	 * @since TBD
	 *
	 * @var array
	 */
	protected $attendee_counts_by_ticket_status = [];

	/**
	 * Add attendees by ticket id.
	 *
	 * @since TBD
	 *
	 * @param int      $ticket_id The ticket id.
	 * @param object[] $attendees The attendees.
	 */
	public function add_attendees_by_ticket_id( $ticket_id, $attendees ) {
		$this->attendees_by_ticket_id[ $ticket_id ] = $attendees;
	}

	/**
	 * Add attendee by ticket id.
	 *
	 * @since TBD
	 *
	 * @param int         $ticket_id The ticket id.
	 * @param object|null $attendee  The attendee object.
	 */
	public function add_attendee_by_ticket_id( $ticket_id, $attendee = null ) {
		if ( ! isset( $this->attendees_by_ticket_id[ $ticket_id ] ) ) {
			$this->attendees_by_ticket_id[ $ticket_id ] = [];
		}

		if ( empty( $attendee ) ) {
			return;
		}

		$this->attendees_by_ticket_id[ $ticket_id ][] = $attendee;
	}

	/**
	 * Get attendees by ticket id.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket id.
	 *
	 * @return object[]|null The attendees.
	 */
	public function get_attendees_by_ticket_id( $ticket_id ) {
		if ( ! isset( $this->attendees_by_ticket_id[ $ticket_id ] ) ) {
			return null;
		}

		return $this->attendees_by_ticket_id[ $ticket_id ];
	}

	/**
	 * Sets individual status quantity for a ticket id.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket id.
	 * @param int $status The status.
	 * @param int $quantity The quantity.
	 */
	public function set_attendee_status_count_by_ticket_id( $ticket_id, $status, $quantity ) {
		if ( ! isset( $this->attendees_by_ticket_status[ $ticket_id ] ) ) {
			$this->attendee_counts_by_ticket_status[ $ticket_id ] = [];
		}

		$this->attendee_counts_by_ticket_status[ $ticket_id ][ $status ] = $quantity;
	}

	/**
	 * Sets all attendee status counts for a ticket by status.
	 *
	 * @since TBD
	 *
	 * @param int   $ticket_id  The ticket ID.
	 * @param int[] $quantities The status quantities.
	 */
	public function set_attendee_status_counts_by_ticket_id( $ticket_id, $quantities ) {
		$this->attendee_counts_by_ticket_status[ $ticket_id ] = $quantities;
	}

	/**
	 * Get the attendee counts for a ticket by status.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket ID.
	 *
	 * @return int[]
	 */
	public function get_attendee_count_by_ticket_status( $ticket_id ) {
		if ( ! isset( $this->attendee_counts_by_ticket_status[ $ticket_id ] ) ) {
			return [];
		}

		return $this->attendee_counts_by_ticket_status[ $ticket_id ];
	}
}
