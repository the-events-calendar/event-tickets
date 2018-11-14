<?php
/**
 * This template renders the RSVP ticket form quantity input
 *
 * @version 0.3.0-alpha
 *
 */
?>
<div class="tribe-block__rsvp__number-input">
	<?php $this->template( 'editor/blocks/rsvp/form/quantity-minus' ); ?>

	<?php $this->template( 'editor/blocks/rsvp/form/quantity-input', array( 'ticket' => $ticket ) ); ?>

	<?php $this->template( 'editor/blocks/rsvp/form/quantity-plus' ); ?>
</div>