<?php
/**
 * This template renders the RSVP ticket form quantity input
 *
 * @since 4.9
 * @since TBD Uses new functions to get singular and plural texts.
 *
 * @version TBD
 */
?>
<div class="tribe-block__rsvp__number-input">
	<div class="tribe-block__rsvp__number-input-inner">
		<?php $this->template( 'blocks/rsvp/form/quantity-minus' ); ?>

		<?php $this->template( 'blocks/rsvp/form/quantity-input', array( 'ticket' => $ticket ) ); ?>

		<?php $this->template( 'blocks/rsvp/form/quantity-plus' ); ?>
	</div>
	<span class="tribe-block__rsvp__number-input-label">
		<?php echo tribe_get_rsvp_label_plural( 'number_input_label' ); ?>
	</span>
</div>
