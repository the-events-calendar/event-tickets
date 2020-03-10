<?php
/**
 * This template renders the RSVP ticket form quantity input.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/rsvp/form/quantity.php
 *
 * @since 4.9
 * @since 4.10.9 Uses new functions to get singular and plural texts.
 * @since TBD Added template override instructions in template comments.
 *
 * @version TBD
 */
?>
<div class="tribe-block__rsvp__number-input">
	<div class="tribe-block__rsvp__number-input-inner">
		<?php $this->template( 'blocks/rsvp/form/quantity-minus' ); ?>

		<?php $this->template( 'blocks/rsvp/form/quantity-input', [ 'ticket' => $ticket ] ); ?>

		<?php $this->template( 'blocks/rsvp/form/quantity-plus' ); ?>
	</div>
	<span class="tribe-block__rsvp__number-input-label">
		<?php echo esc_html( tribe_get_rsvp_label_plural( 'number_input_label' ) ); ?>
	</span>
</div>