<?php
/**
 * RSVP V2: Actions Wrapper
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp-v2/actions/actions.php
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

$step = $this->get( 'step', 'default' );
?>
<div class="tribe-tickets__rsvp-v2-actions-wrapper tribe-common-g-col">
	<div class="tribe-tickets__rsvp-v2-actions">

		<?php if ( in_array( $step, [ 'success', 'opt-in' ], true ) ) : ?>

			<?php $this->template( 'v2/rsvp-v2/actions/success/success', [ 'ticket' => $ticket, 'post_id' => $post_id ] ); ?>

		<?php elseif ( ! $ticket->is_in_stock() ) : ?>

			<?php $this->template( 'v2/rsvp-v2/actions/full', [ 'ticket' => $ticket, 'post_id' => $post_id ] ); ?>

		<?php else : ?>

			<?php $this->template( 'v2/rsvp-v2/actions/rsvp/rsvp', [ 'ticket' => $ticket, 'post_id' => $post_id ] ); ?>

		<?php endif; ?>
	</div>

</div>
