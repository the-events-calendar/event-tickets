<?php
/**
 * RSVP V2: Details Wrapper
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp-v2/details/details.php
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
<div class="tribe-tickets__rsvp-v2-details-wrapper tribe-common-g-col">
	<div class="tribe-tickets__rsvp-v2-details">
		<?php $this->template( 'v2/rsvp-v2/details/title', [ 'ticket' => $ticket, 'post_id' => $post_id ] ); ?>

		<?php $this->template( 'v2/rsvp-v2/details/description', [ 'ticket' => $ticket, 'post_id' => $post_id ] ); ?>

		<?php $this->template( 'v2/rsvp-v2/details/attendance', [ 'ticket' => $ticket, 'post_id' => $post_id ] ); ?>

		<?php $this->template( 'v2/rsvp-v2/details/availability/availability', [ 'ticket' => $ticket, 'post_id' => $post_id ] ); ?>
	</div>
</div>
