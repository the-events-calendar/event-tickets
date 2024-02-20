<?php
use TEC\Tickets\Event;
use Tribe\Tooltip\View as Tooltip_View;

abstract class Tribe__Tickets__Abstract_Attendance_Totals {
	protected $event_id = 0;
	protected $relative_priority = 10;
	/**
	 * The post ID of the event for which totals have been last calculated.
	 *
	 * @since 5.8.2
	 *
	 * @var int
	 */
	private int $calculated_totals_event_id = 0;

	/**
	 * Sets up totals for the specified event.
	 *
	 * @since 4.2.4
	 * @since 5.8.2 Move totals calculation to `set_event_id` method.
	 *
	 * @param int $event_id The event ID to et up the totals for.
	 */
	public function __construct( $event_id = 0 ) {
		$this->set_event_id( $event_id );
	}

	/**
	 * Sets the event ID, based on the provided event ID but defaulting
	 * to the value of the 'event_id' URL param, if set.
	 *
	 * @since 4.2.4
	 * @since 5.8.2 Re-calculate totals when setting a new Event ID.
	 *
	 * @param int $event_id The event ID to set.
	 *
	 * @return bool Whether the event ID was set successfully.
	 */
	public function set_event_id( $event_id = 0 ) {
		if ( $event_id ) {
			$this->event_id = absint( $event_id );
		} elseif ( isset( $_GET['event_id'] ) ) {
			$this->event_id = filter_var( $_GET['event_id'], FILTER_VALIDATE_INT );
		}

		$this->event_id = Event::filter_event_id( $this->event_id );

		$set = (bool) $this->event_id;

		if ( $set && $this->event_id !== $this->calculated_totals_event_id ) {
			$this->calculate_totals();
			$this->calculated_totals_event_id = $this->event_id;
		}

		return $set;
	}

	/**
	 * Calculate total attendance for the current event.
	 */
	abstract protected function calculate_totals();

	/**
	 * Makes the totals available within the attendee summary screen.
	 *
	 * @since 4.2.4
	 * @since 5.8.2
	 */
	public function integrate_with_attendee_screen() {
		add_action( 'tribe_tickets_attendees_totals', array( $this, 'print_totals' ), $this->relative_priority );
	}

	/**
	 * Prints an HTML (unordered) list of attendance totals.
	 */
	abstract public function print_totals();

	/**
	 * Get Attendee Total Sold Tooltip
	 *
	 * @since 4.10.5
	 *
	 * @return string a string of html for the tooltip
	 */
	public function get_total_sold_tooltip() {
		$message = _x( 'Includes all ticketed attendees regardless of order status.', 'total sold tooltip', 'event-tickets' );
		$args = [ 'classes' => 'required' ];

		/** @var Tooltip_View $tooltip */
		$tooltip = tribe( 'tooltip.view' );

		return $tooltip->render_tooltip( $message, $args  );
	}

	/**
	 * Get Attendee Total Completed Orders Tooltip
	 *
	 * @since 4.10.5
	 *
	 * @return string a string of html for the tooltip
	 */
	public function get_total_completed_tooltip() {
		$message = _x( 'Includes ticketed attendees with orders marked Completed.', 'total complete tooltip', 'event-tickets' );
		$args    = [ 'classes' => 'required' ];

		/** @var Tooltip_View $tooltip */
		$tooltip = tribe( 'tooltip.view' );

		return $tooltip->render_tooltip( $message, $args );
	}

	/**
	 * Get Attendee Total Cancelled Orders Tooltip
	 *
	 * @since 4.10.5
	 *
	 * @return string a string of html for the tooltip
	 */
	public function get_total_cancelled_tooltip() {
		// For future use
		return;
	}

	/**
	 * Get Attendee Total Refunded Orders Tooltip
	 *
	 * @since 4.10.8
	 *
	 * @return string a string of html for the tooltip
	 */
	public function get_total_refunded_tooltip() {
		// For future use
		return;
	}
}
