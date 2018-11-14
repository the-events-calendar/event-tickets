<?php
/**
 * This template renders the RSVP ticket details
 *
 * @version 0.3.0-alpha
 *
 */
?>
<div class="tribe-block__rsvp__details">

	<?php $this->template( 'editor/blocks/rsvp/details/title', array( 'ticket' => $ticket ) ); ?>

	<?php $this->template( 'editor/blocks/rsvp/details/description', array( 'ticket' => $ticket ) ); ?>

	<?php $this->template( 'editor/blocks/rsvp/details/availability', array( 'ticket' => $ticket ) ); ?>

</div>