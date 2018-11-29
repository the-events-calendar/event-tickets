<?php
/**
 * This template renders the RSVP ticket form quantity input
 *
 * @version 4.9
 *
 */
?>
<div class="tribe-block__rsvp__number-input">
	<?php $this->template( 'blocks/rsvp/form/quantity-minus' ); ?>

	<?php $this->template( 'blocks/rsvp/form/quantity-input', array( 'ticket' => $ticket ) ); ?>

	<?php $this->template( 'blocks/rsvp/form/quantity-plus' ); ?>
</div>