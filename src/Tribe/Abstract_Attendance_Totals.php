<?php
abstract class Tribe__Tickets__Abstract_Attendance_Totals {
	protected $event_id = 0;
	protected $relative_priority = 10;

	/**
	 * Sets up totals for the specified event.
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
	 * Calculate total attendance for the current event.
	 */
	abstract protected function calculate_totals();

	/**
	 * Makes the totals available within the attendee summary screen.
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
	 * @since TBD
	 *
	 * @return string a string of html for the tooltip
	 */
	public function get_total_sold_tooltip() {
		ob_start();
		?>
		<div class="tribe-tooltip" aria-expanded="false">
			<span class="dashicons dashicons-info"></span>
			<div class="down">
				<?php echo esc_html_x( 'No matter what the status is, Total Tickets Issued includes how many tickets that have gone through the order process.', 'total sold tooltip', 'event-tickets' ); ?><i></i>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get Attendee Total Completed Orders Tooltip
	 *
	 * @since TBD
	 *
	 * @return string a string of html for the tooltip
	 */
	public function get_total_conpleted_tooltip() {
		ob_start();
		?>
		<div class="tribe-tooltip" aria-expanded="false">
			<span class="dashicons dashicons-info"></span>
			<div class="down">
				<?php echo esc_html_x( 'This pertains to Orders that have been marked Completed.', 'total complete tooltip', 'event-tickets' ); ?><i></i>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
