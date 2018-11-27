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
 * @link {INSERT_ARTCILE_LINK_HERE}
 *
 * @version TBD
 *
 */

?>
<div class="tribe-block__rsvp__status">
	<?php if ( $ticket->is_in_stock() ) : ?>

		<?php $this->template( 'blocks/rsvp/status/going', array( 'ticket' => $ticket ) ); ?>
		<?php $this->template( 'blocks/rsvp/status/not-going', array( 'ticket' => $ticket ) ); ?>

	<?php else : ?>
		<?php $this->template( 'blocks/rsvp/status/full', array( 'ticket' => $ticket ) ); ?>
	<?php endif; ?>
</div>
