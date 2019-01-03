<?php
/**
 * Block: RSVP
 * Form base
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/rsvp/form/form.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTCILE_LINK_HERE}
 *
 * @version 4.9
 *
 */
$ticket_id   = $this->get( 'ticket_id' );
$going       = $this->get( 'going' );
$must_login  = ! is_user_logged_in() && tribe( 'tickets.rsvp' )->login_required();
?>
<form
	name="tribe-rsvp-form"
	data-product-id="<?php echo esc_attr( $ticket_id ); ?>"
>
	<input type="hidden" name="product_id[]" value="<?php echo esc_attr( absint( $ticket_id ) ); ?>">
	<input type="hidden" name="attendee[order_status]" value="<?php echo esc_attr( $going ); ?>">
	<!-- Maybe add nonce over here? Try to leave templates as clean as possible -->

	<div class="tribe-left">
		<?php if ( ! $must_login ) : ?>
			<?php $this->template( 'blocks/rsvp/form/quantity', array( 'ticket' => $ticket ) ); ?>
		<?php endif; ?>
	</div>

	<div class="tribe-right">
		<?php $this->template( 'blocks/rsvp/form/error' ); ?>

		<?php $this->template( 'blocks/rsvp/form/submit', array( 'ticket' => $ticket ) ); ?>
	</div>

</form>
