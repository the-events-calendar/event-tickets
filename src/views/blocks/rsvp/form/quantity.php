<?php
/**
 * This template renders the RSVP ticket form quantity input
 *
 * @since 4.9
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
		<?php echo esc_html__( 'RSVPs', 'event-tickets' ); ?>
	</span>
</div>
