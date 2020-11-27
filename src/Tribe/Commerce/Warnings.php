<?php
/**
 * Conditional Warnings on Ticket Features
 *
 * Class Tribe__Tickets__Commerce__Warnings
 *
 * @since TBD
 *
 */
class Tribe__Tickets__Commerce__Warnings {

	public function hook() {
		add_action( 'tribe_events_tickets_new_ticket_buttons', [ $this, 'recurring_event_warning_msg' ] );
		add_action( 'tribe_events_tickets_new_ticket_buttons', [ $this, 'add_commerce_provider_warning' ] );
	}

	/**
	 * Create Recurring Event Warning Message Label
	 *
	 * @since 4.6
	 *
	 * @param int $post_id Post ID.
	 *
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
	 * Add Provider missing warning for tickets.
	 *
	 * @param $post_id
	 */
	public function add_commerce_provider_warning( $post_id ) {
		$available_modules = array_diff_key( Tribe__Tickets__Tickets::modules(), [ 'Tribe__Tickets__RSVP' => true ] );

		if ( count( $available_modules ) > 0 ) {
			return;
		}

		$kb_link = 'http://m.tri.be/1ao5';
		$message = sprintf( __( 'There is no payment gateway configured. To create tickets, you\'ll need to enable and configure an ecommerce solution. <a href="%1$s" target="_blank" rel="noopener noreferrer">[Learn more]</a>', 'event-tickets' ), $kb_link );

		$this->show_notice( $message );
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
		<div class="ticket-editor-notice <?php echo esc_attr( $type ) ?>">
			<span class="dashicons <?php echo esc_attr( $icon ); ?>"></span>
			<span class="message"><?php echo wp_kses_post( $msg, 'event-tickets' ); ?></span>
		</div>
		<?php
	}
}