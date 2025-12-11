<?php
/**
 * RSVP V2: Buttons Container
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp-v2/form/buttons.php
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
 * @var string                        $going   The RSVP status ('going' or 'not-going').
 */

// Ensure proper context.
if ( empty( $ticket ) || empty( $post_id ) ) {
	return;
}
?>
<div class="tribe-tickets__rsvp-v2-form-buttons">
	<?php $this->template( 'v2/rsvp-v2/form/fields/cancel', [ 'ticket' => $ticket, 'post_id' => $post_id, 'going' => $going ] ); ?>
	<?php $this->template( 'v2/rsvp-v2/form/fields/submit', [ 'ticket' => $ticket, 'post_id' => $post_id, 'going' => $going ] ); ?>
</div>
