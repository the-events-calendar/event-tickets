<?php
/**
 * RSVP V2: Success Wrapper
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp-v2/messages/success/success.php
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
 * @var string|null                   $step    The step the views are on.
 */

// Ensure proper context.
if ( empty( $ticket ) || empty( $post_id ) ) {
	return;
}

if ( ! in_array( $step, [ 'success', 'opt-in' ], true ) ) {
	return;
}
?>
<div class="tribe-tickets__rsvp-v2-message tribe-tickets__rsvp-v2-message--success tribe-common-b3">
	<?php $this->template( 'v2/components/icons/paper-plane', [ 'classes' => [ 'tribe-tickets__rsvp-v2-message--success-icon' ] ] ); ?>

	<?php $this->template( 'v2/rsvp-v2/messages/success/going', [ 'ticket' => $ticket, 'post_id' => $post_id ] ); ?>

	<?php $this->template( 'v2/rsvp-v2/messages/success/not-going', [ 'ticket' => $ticket, 'post_id' => $post_id ] ); ?>

</div>
