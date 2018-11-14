<?php
/**
 * This template renders the RSVP ticket content
 *
 * @version TBD
 *
 */
?>
<div class="tribe-block__rsvp__content">

	<div class="tribe-block__rsvp__details__status">
		<?php $this->template( 'editor/blocks/rsvp/details', array( 'ticket' => $ticket ) ); ?>
		<?php $this->template( 'editor/blocks/rsvp/status', array( 'ticket' => $ticket ) ); ?>
	</div>

	<?php $this->template( 'editor/blocks/rsvp/form', array( 'ticket' => $ticket ) ); ?>

</div>