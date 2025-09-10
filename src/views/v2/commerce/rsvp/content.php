<?php
/**
 * Block: RSVP
 * Content
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/rsvp/content.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @var Tribe__Tickets__Ticket_Object $rsvp The rsvp ticket object.
 * @var string|null $step The step the views are on.
 *
 * @since TBD
 *
 * @version TBD
 */

?>

<?php $this->template( 'v2/commerce/rsvp/messages/must-login' ); ?>

<?php if ( 'ari' === $step ) : ?>

	<?php $this->template( 'v2/commerce/rsvp/ari', [ 'rsvp' => $rsvp ] ); ?>

<?php elseif ( 'going' === $step || 'not-going' === $step ) : ?>

	<?php $this->template( 'v2/commerce/rsvp/form/form', [ 'rsvp' => $rsvp, 'going' => $step ] ); ?>

<?php else : ?>

	<?php $this->template( 'v2/commerce/rsvp/messages/success' ); ?>

	<div class="tribe-tickets__rsvp tribe-common-g-row tribe-common-g-row--gutters">

		<?php $this->template( 'v2/commerce/rsvp/details', [ 'rsvp' => $rsvp ] ); ?>

		<?php $this->template( 'v2/commerce/rsvp/actions', [ 'rsvp' => $rsvp ] ); ?>

		<?php $this->do_entry_point( 'etp-waitlist' ); ?>

	</div>

	<?php $this->template( 'v2/commerce/rsvp/attendees' ); ?>

<?php endif; ?>
