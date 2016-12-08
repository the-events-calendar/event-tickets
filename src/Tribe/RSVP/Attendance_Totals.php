<?php
/**
 * Calculates RSVP attendance totals for a specified event (ie, how many
 * are going, not going, etc).
 *
 * Also has the capability to print this information as HTML, intended for
 * use in the attendee summary screen.
 *
 * Note that the totals are calculated upon instantiation, effectively making
 * the object a snapshot in time. Therefore if the status of RSVPs is modified
 * or if RSVPs are added/deleted later in the request, it would be necessary
 * to obtain a new object of this type to get accurate results.
 */
class Tribe__Tickets__RSVP__Attendance_Totals extends Tribe__Tickets__Abstract_Attendance_Totals {
	protected $total_rsvps = 0;
	protected $total_going = 0;
	protected $total_not_going = 0;

	/**
	 * Calculate total RSVP attendance for the current event.
	 */
	protected function calculate_totals() {
		foreach ( Tribe__Tickets__RSVP::get_instance()->get_attendees_array( $this->event_id ) as $attendee ) {
			switch( $attendee[ 'order_status' ] ) {
				case 'yes': $this->total_going++; break;
				case 'no': $this->total_not_going++; break;
			}
		}

		$this->total_rsvps = $this->total_going + $this->total_not_going;
	}

	/**
	 * Prints an HTML (unordered) list of attendance totals.
	 */
	public function print_totals() {
		$total_rsvps_label = esc_html_x( 'Total RSVPs:', 'attendee summary', 'event-tickets' );
		$going_label = esc_html_x( 'Going:', 'attendee summary', 'event-tickets' );
		$not_going_label = esc_html_x( 'Not Going:', 'attendee summary', 'event-tickets' );

		$total_rsvps = $this->get_total_rsvps();
		$going = $this->get_total_going();
		$not_going = $this->get_total_not_going();

		$html = "
			<ul>
				<li> <strong>$total_rsvps_label</strong> $total_rsvps </li>
				<li> $going_label $going </li>
				<li> $not_going_label $not_going </li>
			</ul>
		";

		/**
		 * Filters the HTML that should be printed to display RSVP attendance lines.
		 *
		 * @param string $html The default HTML code displaying going and not going data.
		 */
		$html = apply_filters( 'tribe_tickets_rsvp_print_totals_html', $html );

		echo $html;
	}

	/**
	 * The total number of RSVPs received for this event.
	 *
	 * @return int
	 */
	public function get_total_rsvps() {
		/**
		 * Returns the total RSVP count for an event.
		 *
		 * @param int $total_rsvps
		 * @param int $original_total_rsvps
		 * @param int $event_id
		 */
		return (int) apply_filters( 'tribe_tickets_rsvp_get_total_rsvps', $this->total_rsvps, $this->total_rsvps, $this->event_id );
	}

	/**
	 * The total number of RSVPs for this event that indicate they are
	 * going.
	 *
	 * @return int
	 */
	public function get_total_going() {
		/**
		 * Returns the total going count for an event.
		 *
		 * @param int $total_going
		 * @param int $original_total_going
		 * @param int $event_id
		 */
		return (int) apply_filters( 'tribe_tickets_rsvp_get_total_going', $this->total_going, $this->total_going, $this->event_id );
	}

	/**
	 * The total number of RSVPs for this event that indicate they are
	 * not going.
	 *
	 * @return int
	 */
	public function get_total_not_going() {
		/**
		 * Returns the total not going count for an event.
		 *
		 * @param int $total_not_going
		 * @param int $original_total_not_going
		 * @param int $event_id
		 */
		return (int) apply_filters( 'tribe_tickets_rsvp_get_total_not_going', $this->total_not_going, $this->total_not_going, $this->event_id );
	}
}