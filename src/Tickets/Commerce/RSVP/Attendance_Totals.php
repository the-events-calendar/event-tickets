<?php
/**
 * Calculates TC RSVP attendance totals for a specified event (ie, how many
 * are going, not going, etc).
 *
 * Also has the capability to print this information as HTML, intended for
 * use in the attendee summary screen.
 *
 * Note that the totals are calculated upon instantiation, effectively making
 * the object a snapshot in time. Therefore if the status of RSVPs is modified
 * or if RSVPs are added/deleted later in the request, it would be necessary
 * to obtain a new object of this type to get accurate results.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\RSVP
 */

namespace TEC\Tickets\Commerce\RSVP;

/**
 * Class Attendance_Totals.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\RSVP
 */
class Attendance_Totals extends \Tribe__Tickets__Abstract_Attendance_Totals {

	/**
	 * Total number of RSVPs.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	protected $total_rsvps = 0;

	/**
	 * Total number of people going.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	protected $total_going = 0;

	/**
	 * Total number of people not going.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	protected $total_not_going = 0;

	/**
	 * Whether RSVP is enabled for this event.
	 *
	 * @since TBD
	 *
	 * @var bool
	 */
	protected $has_rsvp_enabled = false;

	/**
	 * Calculate total RSVP attendance for the current event.
	 *
	 * @since TBD
	 */
	protected function calculate_totals() {
		$tickets = \Tribe__Tickets__Tickets::get_event_tickets( $this->event_id );

		foreach ( $tickets as $ticket ) {
			/** @var \Tribe__Tickets__Ticket_Object $ticket */
			if ( ! $this->should_count( $ticket ) ) {
				continue;
			}

			$this->has_rsvp_enabled = true;

			// Get attendees for this TC RSVP ticket using the Commerce provider directly.
			$attendee_data = \Tribe__Tickets__Tickets::get_event_attendees_by_args(
				$this->event_id,
				[
					'provider' => 'tec-tickets-commerce',
					'by' => [
						'ticket' => $ticket->ID,
					]
				]
			);

			foreach ( $attendee_data['attendees'] as $attendee ) {
				// Only process TC RSVP attendees.
				if ( empty( $attendee['ticket_type'] ) || Constants::TC_RSVP_TYPE !== $attendee['ticket_type'] ) {
					continue;
				}

				if ( ! isset( $attendee['rsvp_status'] ) ) {
					continue;
				}

				if ( 'yes' === $attendee['rsvp_status'] ) {
					++$this->total_going;
				} elseif ( 'no' === $attendee['rsvp_status'] ) {
					++$this->total_not_going;
				}
			}
		}

		$this->total_rsvps = $this->total_going + $this->total_not_going;
	}

	/**
	 * Indicates if the ticket should be factored into our RSVP counts.
	 *
	 * @since TBD
	 *
	 * @param \Tribe__Tickets__Ticket_Object $ticket The ticket object to check.
	 *
	 * @return bool
	 */
	protected function should_count( \Tribe__Tickets__Ticket_Object $ticket ) {
		// Only count TC RSVP tickets.
		$should_count = Constants::TC_RSVP_TYPE === $ticket->type();

		/**
		 * Determine if the provided ticket object should be used when building
		 * TC RSVP counts.
		 *
		 * By default, only tickets of type tc-rsvp are counted.
		 *
		 * @since TBD
		 *
		 * @param bool                           $should_count Whether the ticket should be counted.
		 * @param \Tribe__Tickets__Ticket_Object $ticket       The ticket object.
		 */
		return (bool) apply_filters( 'tec_tickets_commerce_rsvp_should_use_ticket_in_counts', $should_count, $ticket );
	}

	/**
	 * Prints an HTML (unordered) list of attendance totals.
	 *
	 * @since TBD
	 */
	public function print_totals() {
		// Skip output if there are no RSVP attendees going/not going AND if there are no current RSVP tickets.
		if (
			false === $this->has_rsvp_enabled
			&& 0 === $this->get_total_rsvps()
		) {
			return;
		}

		$args = [
			'total_type_label'        => tribe_get_rsvp_label_plural( 'total_type_label' ),
			/* translators: %s: Plural RSVP label (e.g., "RSVPs"). */
			'total_sold_label'        => esc_html( sprintf( _x( 'Total %s:', 'attendee summary', 'event-tickets' ), tribe_get_rsvp_label_plural( 'total_sold_label' ) ) ),
			'total_complete_label'    => _x( 'Going:', 'attendee summary', 'event-tickets' ),
			'total_cancelled_label'   => _x( 'Not Going:', 'attendee summary', 'event-tickets' ),
			'total_sold'              => $this->get_total_rsvps(),
			'total_complete'          => $this->get_total_going(),
			'total_cancelled'         => $this->get_total_not_going(),
			'total_refunded'          => 0,
			'total_sold_tooltip'      => '',
			'total_completed_tooltip' => '',
			'total_cancelled_tooltip' => '',
			'total_refunded_tooltip'  => '',
		];

		$html = tribe( 'tickets.admin.views' )->template( 'attendees/attendees-event/totals-list', $args, false );

		/**
		 * Filters the HTML that should be printed to display TC RSVP attendance lines.
		 *
		 * @since TBD
		 *
		 * @param string $html The default HTML code displaying going and not going data.
		 */
		$html = apply_filters( 'tec_tickets_commerce_rsvp_print_totals_html', $html );

		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped,StellarWP.XSS.EscapeOutput.OutputNotEscaped
	}

	/**
	 * The total number of RSVPs received for this event.
	 *
	 * @since TBD
	 *
	 * @return int
	 */
	public function get_total_rsvps() {
		/**
		 * Returns the total RSVP count for an event.
		 *
		 * @since TBD
		 *
		 * @param int $total_rsvps          The filtered total RSVP count.
		 * @param int $original_total_rsvps The original total RSVP count before filtering.
		 * @param int $event_id             The event ID.
		 */
		return (int) apply_filters( 'tec_tickets_commerce_rsvp_get_total_rsvps', $this->total_rsvps, $this->total_rsvps, $this->event_id );
	}

	/**
	 * The total number of RSVPs for this event that indicate they are
	 * going.
	 *
	 * @since TBD
	 *
	 * @return int
	 */
	public function get_total_going() {
		/**
		 * Returns the total going count for an event.
		 *
		 * @since TBD
		 *
		 * @param int $total_going          The filtered total going count.
		 * @param int $original_total_going The original total going count before filtering.
		 * @param int $event_id             The event ID.
		 */
		return (int) apply_filters( 'tec_tickets_commerce_rsvp_get_total_going', $this->total_going, $this->total_going, $this->event_id );
	}

	/**
	 * The total number of RSVPs for this event that indicate they are
	 * not going.
	 *
	 * @since TBD
	 *
	 * @return int
	 */
	public function get_total_not_going() {
		/**
		 * Returns the total not going count for an event.
		 *
		 * @since TBD
		 *
		 * @param int $total_not_going          The filtered total not going count.
		 * @param int $original_total_not_going The original total not going count before filtering.
		 * @param int $event_id                 The event ID.
		 */
		return (int) apply_filters( 'tec_tickets_commerce_rsvp_get_total_not_going', $this->total_not_going, $this->total_not_going, $this->event_id );
	}
}
