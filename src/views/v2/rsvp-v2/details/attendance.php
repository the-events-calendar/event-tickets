<?php
/**
 * RSVP V2: Attendance Count
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp-v2/details/attendance.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var Tribe__Tickets__Ticket_Object $ticket  The RSVP ticket object.
 * @var int                           $post_id The event post ID.
 */

// Ensure proper context.
if ( empty( $ticket ) || empty( $post_id ) ) {
	return;
}

$classes = [ 'tribe-tickets__rsvp-v2-attendance-number', 'tribe-common-h4' ];
if ( ! $ticket->show_description() || empty( $ticket->description ) ) {
	$classes[] = 'tribe-tickets__rsvp-v2-attendance-number--no-description';
}
?>
<div class="tribe-tickets__rsvp-v2-attendance">
	<span <?php tribe_classes( $classes ); ?>>
		<?php echo esc_html( $ticket->qty_sold ); ?>
	</span>
	<span class="tribe-tickets__rsvp-v2-attendance-going tribe-common-h7 tribe-common-h--alt tribe-common-b3--min-medium">
		<?php echo esc_html_x( 'Going', 'Label below the attendance number', 'event-tickets' ); ?>
	</span>
</div>
