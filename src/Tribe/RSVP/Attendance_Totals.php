<?php
/**
 * Calculates RSVP attendance totals for a specified event (ie, how many
 * are going, not going, etc).
 *
 * Also has the capability to print this information as HTML, intended for
 * use in the attendee summary screen.
 */
class Tribe__Tickets__RSVP__Attendance_Totals {
	protected $event_id = 0;
	protected $total_rsvps = 0;
	protected $total_going = 0;
	protected $total_not_going = 0;

	/**
	 * Determines current RSVP totals for the current or specified event.
	 *
	 * @param int $event_id
	 */
	public function __construct( $event_id = 0 ) {
		if ( ! $this->set_event_id( $event_id ) ) {
			return;
		}

		$this->calculate_totals();
	}

	/**
	 * Sets the event ID, based on the provided event ID but defaulting
	 * to the value of the 'event_id' URL param, if set.
	 *
	 * @param int $event_id
	 *
	 * @return bool
	 */
	protected function set_event_id( $event_id = 0 ) {
		if ( $event_id ) {
			$this->event_id = absint( $event_id );
		} elseif ( isset( $_GET[ 'event_id' ] ) ) {
			$this->event_id = absint( $_GET[ 'event_id' ] );
		}

		return (bool) $this->event_id;
	}

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
	 * Makes the RSVP totals available within the attendee summary screen.
	 */
	public function integrate_with_attendee_screen() {
		add_action( 'tribe_tickets_attendees_totals', array( $this, 'print_totals' ) );
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

		echo "
			<ul>
				<li> <strong>$total_rsvps_label</strong> $total_rsvps </li>
				<li> <strong>$going_label</strong> $going </li>
				<li> <strong>$not_going_label</strong> $not_going </li>
			</ul>
		";
	}

	public function get_total_rsvps() {
		/**
		 * Returns the total RSVP count for an event.
		 *
		 * @param int $total_rsvps
		 * @param int $original_total_rsvps
		 * @param int $event_id
		 */
		return apply_filters( 'tribe_tickets_rsvp_get_total_rsvps', $this->total_rsvps, $this->total_rsvps, $this->event_id );
	}

	public function get_total_going() {
		/**
		 * Returns the total going count for an event.
		 *
		 * @param int $total_going
		 * @param int $original_total_going
		 * @param int $event_id
		 */
		return apply_filters( 'tribe_tickets_rsvp_get_total_going', $this->total_going, $this->total_going, $this->event_id );
	}

	public function get_total_not_going() {
		/**
		 * Returns the total not going count for an event.
		 *
		 * @param int $total_not_going
		 * @param int $original_total_not_going
		 * @param int $event_id
		 */
		return apply_filters( 'tribe_tickets_rsvp_get_total_not_going', $this->total_not_going, $this->total_not_going, $this->event_id );
	}
}