<?php
/**
 * RSVP V2: Content Container
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp-v2/content.php
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

<?php $this->template( 'v2/rsvp-v2/messages/must-login', [ 'ticket' => $ticket, 'post_id' => $post_id ] ); ?>

<?php if ( 'ari' === $step ) : ?>

	<?php $this->template( 'v2/rsvp-v2/ari/ari', [ 'ticket' => $ticket, 'post_id' => $post_id ] ); ?>

<?php elseif ( 'going' === $step || 'not-going' === $step ) : ?>

	<?php $this->template( 'v2/rsvp-v2/form/form', [ 'ticket' => $ticket, 'post_id' => $post_id, 'going' => $step ] ); ?>

<?php else : ?>

	<?php $this->template( 'v2/rsvp-v2/messages/success/success', [ 'ticket' => $ticket, 'post_id' => $post_id ] ); ?>

	<div class="tribe-tickets__rsvp-v2-content tribe-common-g-row tribe-common-g-row--gutters">

		<?php $this->template( 'v2/rsvp-v2/details/details', [ 'ticket' => $ticket, 'post_id' => $post_id ] ); ?>

		<?php $this->template( 'v2/rsvp-v2/actions/actions', [ 'ticket' => $ticket, 'post_id' => $post_id ] ); ?>

	</div>

<?php endif; ?>
