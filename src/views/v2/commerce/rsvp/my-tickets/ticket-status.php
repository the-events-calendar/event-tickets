<?php
/**
 * RSVP V2: My Tickets - Ticket Status
 *
 * Renders the RSVP going/not-going status for a TC-RSVP attendee
 * on the My Tickets page.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/rsvp/my-tickets/ticket-status.php
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var bool $attendee_is_going Whether the attendee is going.
 * @var bool $show_not_going    Whether the "Not going" option is available.
 * @var int  $attendee_id       The attendee ID.
 */

defined( 'ABSPATH' ) || exit;

if ( $show_not_going ) {
	?>
	<span class="ticket-status">
		<?php esc_html_e( 'Response:', 'event-tickets' ); ?>
		<select name="attendee[<?php echo esc_attr( $attendee_id ); ?>][order_status]" class="ticket-status-select">
			<option value="going" <?php selected( $attendee_is_going, true ); ?>><?php esc_html_e( 'Going', 'event-tickets' ); ?></option>
			<option value="not_going" <?php selected( $attendee_is_going, false ); ?>><?php esc_html_e( 'Not going', 'event-tickets' ); ?></option>
		</select>
	</span>
	<?php
} else {
	?>
	<span class="ticket-status">
		<?php esc_html_e( 'Response:', 'event-tickets' ); ?>
		<span class="ticket-status-value"><?php echo $attendee_is_going ? esc_html__( 'Going', 'event-tickets' ) : esc_html__( 'Not going', 'event-tickets' ); ?></span>
	</span>
	<?php
}
