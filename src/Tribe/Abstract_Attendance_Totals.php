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
		$message = _x( 'No matter what the status is, Total Tickets Issued includes how many tickets that have gone through the order process.', 'total sold tooltip', 'event-tickets' );

		return $this->build_tooltip( $message, 'required' );
	}

	/**
	 * Get Attendee Total Completed Orders Tooltip
	 *
	 * @since TBD
	 *
	 * @return string a string of html for the tooltip
	 */
	public function get_total_completed_tooltip() {
		$message = _x( 'This pertains to Orders that have been marked Completed.', 'total complete tooltip', 'event-tickets' );

		return $this->build_tooltip( $message, 'required' );
	}

	/**
	 * Factory method for tooltips
	 *
	 * @since TBD
	 *
	 * @TODO: this should get moved to common?
	 *
	 * @param array|string $message array of messages or single message as string
	 * @param array $args extra arguments, defaults include icon, classes, direction, anmd context (for the filter)
	 * @return string a string of html for the tooltip
	 */
	private function build_tooltip( $message, $args = [] ) {
		if ( empty( $message ) ) {
			return;
		}

		$default_args = [
			'classes'   => '',
			'icon'      => 'info',
			'direction' => 'down',
			'context'   => '',
		];

		$merged_args = wp_parse_args( $args, $default_args );

		ob_start();
		?>
		<div class="tribe-tooltip" aria-expanded="false">
			<span class="dashicons dashicons-<?php esc_attr_e( $merged_args[ 'icon' ] ); ?> <?php esc_attr_e( $merged_args[ 'additional_classes' ] ); ?>"></span>
			<div class="<?php echo sanitize_html_class( $merged_args[ 'direction' ] ); ?>">
				<?php if ( is_array( $message ) ) {
					foreach( $message as $mess ) : ?>
						<p>
							<span><?php echo wp_kses_post( $mess ); ?><i></i></span>
						</p>
					<?php }
				} else { ?>
					<p>
						<span><?php echo wp_kses_post( $message ); ?><i></i></span>
					</p>
				<?php } ?>
			</div>
		</div>
		<?php
		$html = ob_get_clean();

		/**
		 * Allow us to filter the tooltip output
		 *
		 * @since  TBD
		 *
		 * @param string $html The tooltip HTML
		 * @param array|string $message array of messages or single message as string
		 * @param array $args extra arguments, defaults include icon, classes, and direction
		 */
		return apply_filters( 'tribe_tooltip_html', $html, $message, $args );
	}
}
