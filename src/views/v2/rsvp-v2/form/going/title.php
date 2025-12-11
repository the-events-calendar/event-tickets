<?php
/**
 * RSVP V2: Going Form Title
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp-v2/form/going/title.php
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
<div class="tribe-tickets__rsvp-v2-form-title">
	<h3 class="tribe-common-h5">
		<?php esc_html_e( 'Please submit your RSVP information, including the total number of guests.', 'event-tickets' ); ?>
	</h3>
</div>
