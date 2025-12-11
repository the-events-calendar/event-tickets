<?php
/**
 * RSVP V2: Going/Not Going Container
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp-v2/actions/rsvp/rsvp.php
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
<div class="tribe-tickets__rsvp-v2-actions-rsvp" data-rsvp-v2-actions>
	<span class="tribe-common-h2 tribe-common-h6--min-medium">
		<?php esc_html_e( 'RSVP Here', 'event-tickets' ); ?>
	</span>

	<?php $this->template( 'v2/rsvp-v2/actions/rsvp/going', [ 'ticket' => $ticket, 'post_id' => $post_id ] ); ?>

	<?php $this->template( 'v2/rsvp-v2/actions/rsvp/not-going', [ 'ticket' => $ticket, 'post_id' => $post_id ] ); ?>

</div>
