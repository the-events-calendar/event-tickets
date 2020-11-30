<?php
/**
 * Conditional Warnings on Ticket Features
 *
 * Class Tribe__Tickets__Commerce__Warnings
 *
 * @since TBD
 */
class Tribe__Tickets__Commerce__Warnings {

	/**
	 * Hooks actions for showing warnings
	 */
	public function hook() {
		add_action( 'tribe_events_tickets_new_ticket_buttons', [ $this, 'recurring_event_warning_msg' ] );
	}

	/**
	 * Create Recurring Event Warning Message Label
	 *
	 * @since 4.6
	 *
	 * @param int $post_id Post ID.
	 */
	public function recurring_event_warning_msg( $post_id ) {
		if ( ! class_exists( 'Tribe__Events__Pro__Main' ) ) {
			return;
		}

		if ( ! function_exists( 'tribe_is_recurring_event' ) ) {
			return;
		}

		if ( ! tribe_is_recurring_event( $post_id ) ) {
			return;
		}

		$warning = __( 'This is a recurring event. If you add tickets, they will only show up on first event in the recurrence series. Please carefully configure your recurring events.', 'event-tickets' );

		$this->show_notice( $warning );
	}

	/**
	 * Render the notice block.
	 *
	 * @since TBD
	 *
	 * @param string $msg Message to show.
	 * @param string $type Type of message.
	 */
	public function show_notice( $msg, $type = 'info' ) {
		$icon = 'dashicons-' . $type;
		?>
		<div class="ticket-editor-notice <?php echo esc_attr( $type ); ?>">
			<span class="dashicons <?php echo esc_attr( $icon ); ?>"></span>
			<span class="message"><?php esc_html_e( $msg, 'event-tickets' ); ?></span>
		</div>
		<?php
	}
}
