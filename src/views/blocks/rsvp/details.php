<?php
/**
 * Block: RSVP
 * Details
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/rsvp/details.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link https://m.tri.be/1amp Help article for RSVP & Ticket template files.
 *
 * @since 4.9
 * @version 4.9.4
 *
 */

?>
<div class="tribe-block__rsvp__details">

	<?php $this->template( 'blocks/rsvp/details/title', array( 'ticket' => $ticket ) ); ?>

	<?php $this->template( 'blocks/rsvp/details/description', array( 'ticket' => $ticket ) ); ?>

	<?php $this->template( 'blocks/rsvp/details/availability', array( 'ticket' => $ticket ) ); ?>

</div>
