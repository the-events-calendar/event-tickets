<?php
/**
 * RSVP V2: RSVP Full Message
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp-v2/details/availability/full.php
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
?>
<span class="tribe-tickets__rsvp-v2-availability-full">
	<?php echo esc_html_x( 'RSVPs full', 'RSVP is at capacity', 'event-tickets' ); ?>
</span>
