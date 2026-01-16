<?php
/**
 * Block: RSVP
 * Attendees - Title
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp/attendees/title.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @var Tribe__Tickets__Ticket_Object $rsvp The rsvp ticket object.
 * @var string|null $step The step the views are on.
 * @var array $attendees  List of attendees for the given order.
 *
 * @since 5.7.0
 *
 * @version 5.7.0
 */

if ( empty( $attendees ) ) {
	return;
}
?>
<h4 class="tribe-common-h4 tribe-common-h6--min-medium">
	<?php
	echo sprintf(
		// Translators: %s is the plural label for RSVPs.
		esc_html__( 'Your %s', 'event-tickets' ),
		tribe_get_rsvp_label_plural( 'rsvp_success_page_your_tickets' )
	); ?>
</h4>