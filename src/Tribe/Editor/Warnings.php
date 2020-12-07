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
		add_action( 'tribe_events_tickets_new_ticket_buttons', [ $this, 'add_commerce_provider_warning' ] );
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

		$this->render_notice( $warning );
	}

	/**
	 * Add Provider missing warning for tickets.
	 *
	 * @since TBD
	 */
	public function add_commerce_provider_warning() {
		$available_modules = array_diff_key( \Tribe__Tickets__Tickets::modules(), [ 'Tribe__Tickets__RSVP' => true ] );

		if ( count( $available_modules ) > 0 ) {
			return;
		}

		$this->render_notice( $this->get_commerce_provider_missing_warning_message() );
	}

	/**
	 * Get the Commerce Provider missing warning message.
	 *
	 * @since TBD
	 *
	 * @return string The Commerce Provider missing message.
	 */
	public function get_commerce_provider_missing_warning_message() {
		$kb_url = 'http://m.tri.be/1ao5';

		/* translators: %1$s: URL for help link, %2$s: Label for help link. */
		$link = sprintf(
			'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
			esc_url( $kb_url ),
			esc_html_x( 'Learn More', 'Helper link in Ticket Editor', 'event-tickets' )
		);

		return wp_kses_post(
			sprintf(
				/* translators: %1$s: link to help article. */
				__( 'There is no payment gateway configured. To create tickets, you\'ll need to enable and configure an ecommerce solution. %1$s', 'event-tickets' ),
				$link
			)
		);
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
	 * @param string $message Tee message to show.
	 * @param string $type    Type of message.
	 */
	public function render_notice( $message, $type = 'info' ) {
		$icon = 'dashicons-' . $type;
		?>
		<div class="ticket-editor-notice <?php echo esc_attr( $type ); ?>">
			<span class="dashicons <?php echo esc_attr( $icon ); ?>"></span>
			<span class="message"><?php echo wp_kses_post( $message ); ?></span>
		</div>
		<?php
	}
}
