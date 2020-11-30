<?php
/**
 * Conditional Warnings for the Editor.
 */

namespace Tribe\Tickets\Editor;

/**
 * Warnings handling class.
 *
 * @since TBD
 */
class Warnings {

	/**
	 * Hooks actions for showing warnings
	 *
	 * @since TBD
	 */
	public function hook() {
		add_action( 'tribe_events_tickets_new_ticket_buttons', [ $this, 'show_recurring_event_warning_message' ] );
	}

	/**
	 * Show the Recurring Event warning message.
	 *
	 * @since TBD
	 *
	 * @param int $post_id Post ID.
	 */
	public function show_recurring_event_warning_message( $post_id ) {
		if ( ! class_exists( 'Tribe__Events__Pro__Main' ) ) {
			return;
		}

		if ( ! function_exists( 'tribe_is_recurring_event' ) ) {
			return;
		}

		if ( ! tribe_is_recurring_event( $post_id ) ) {
			return;
		}

		$warning = $this->get_recurring_event_warning_message();

		$this->show_notice( $warning );
	}

	/**
	 * Get the Recurring Event warning message.
	 *
	 * @since TBD
	 *
	 * @return string The Recurring Event warning message.
	 */
	public function get_recurring_event_warning_message() {
		return __( 'This is a recurring event. If you add tickets, they will only show up on first event in the recurrence series. Please carefully configure your recurring events.', 'event-tickets' );
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
