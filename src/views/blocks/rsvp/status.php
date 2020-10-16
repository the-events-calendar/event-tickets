<?php
/**
 * Block: RSVP
 * Status
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/rsvp/status.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link https://m.tri.be/1amp Help article for RSVP & Ticket template files.
 *
 * @since 4.9
 * @version 4.9.4
 *
 */
$going = $this->get( 'going' );
?>
<div class="tribe-block__rsvp__status">
	<?php if ( $ticket->is_in_stock() ) : ?>

		<?php $this->template( 'blocks/rsvp/status/going', array( 'ticket' => $ticket, 'going' => $going ) ); ?>
		<?php $this->template( 'blocks/rsvp/status/not-going', array( 'ticket' => $ticket, 'going' => $going ) ); ?>

	<?php else : ?>
		<?php $this->template( 'blocks/rsvp/status/full', array( 'ticket' => $ticket ) ); ?>
	<?php endif; ?>
</div>
