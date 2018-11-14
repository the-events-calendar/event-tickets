<?php
/**
 * This template renders the RSVP ticket form
 *
 * @version 0.3.0-alpha
 *
 */
$ticket_id = $this->get( 'ticket_id' );
$going     = $this->get( 'going' );
?>
<form
	name="tribe-rsvp-form"
	data-product-id="<?php echo esc_attr( $ticket_id ); ?>"
>
	<input type="hidden" name="product_id[]" value="<?php echo esc_attr( absint( $ticket_id ) ); ?>">
	<input type="hidden" name="attendee[order_status]" value="<?php echo esc_attr( $going ); ?>">
	<!-- Maybe add nonce over here? Try to leave templates as clean as possible -->

	<div class="tribe-left">
		<?php $this->template( 'editor/blocks/rsvp/form/quantity', array( 'ticket' => $ticket ) ); ?>
	</div>

	<div class="tribe-right">
		<?php $this->template( 'editor/blocks/rsvp/form/error' ); ?>

		<?php $this->template( 'editor/blocks/rsvp/form/name', array( 'ticket' => $ticket ) ); ?>

		<?php $this->template( 'editor/blocks/rsvp/form/email', array( 'ticket' => $ticket ) ); ?>

		<?php $this->template( 'editor/blocks/rsvp/form/opt-out', array( 'ticket' => $ticket ) ); ?>

		<?php $this->template( 'editor/blocks/rsvp/form/submit', array( 'ticket' => $ticket ) ); ?>
	</div>

</form>
