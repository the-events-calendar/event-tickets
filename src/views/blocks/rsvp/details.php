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
 * @link {INSERT_ARTCILE_LINK_HERE}
 *
 * @version 4.9
 *
 */

?>
<div class="tribe-block__rsvp__details">

	<?php $this->template( 'blocks/rsvp/details/title', array( 'ticket' => $ticket ) ); ?>

	<?php $this->template( 'blocks/rsvp/details/description', array( 'ticket' => $ticket ) ); ?>

	<?php $this->template( 'blocks/rsvp/details/availability', array( 'ticket' => $ticket ) ); ?>

</div>