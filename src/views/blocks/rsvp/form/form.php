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
 * @link    {INSERT_ARTICLE_LINK_HERE}
 *
 * @since   4.9
 * @since   4.12.0 Add $post_id to filter for hiding opt-outs.
 * @since   4.12.3 Add comments to help IDE auto-completion. Array short syntax.
 *
 * @version 4.12.3
 */

$ticket_id = $this->get( 'ticket_id' );
$post_id   = $this->get( 'post_id' );
$going     = $this->get( 'going' );

/** @var Tribe__Tickets__Tickets_Handler $tickets_handler */
$tickets_handler = tribe( 'tickets.handler' );
$ticket_data     = $tickets_handler->get_object_connections( $ticket_id );

$event_id = $ticket_data->event;

/** @var Tribe__Tickets__RSVP $rsvp */
$rsvp       = tribe( 'tickets.rsvp' );
$must_login = ! is_user_logged_in() && $rsvp->login_required();
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
			<?php $this->template( 'blocks/rsvp/form/quantity', [ 'ticket' => $ticket ] ); ?>
		<?php endif; ?>
	</div>

	<div class="tribe-right">
		<?php $this->template( 'blocks/rsvp/form/error' ); ?>

		<?php if ( $must_login ) : ?>
			<?php $this->template( 'blocks/rsvp/form/submit-login', [ 'event_id' => $event_id, 'going' => $going, 'ticket_id' => $ticket_id ] ); ?>
		<?php else : ?>
			<?php $this->template( 'blocks/rsvp/form/details', [ 'ticket' => $ticket, 'post_id' => $post_id ] ); ?>
			<?php $this->template( 'blocks/rsvp/form/attendee-meta', [ 'ticket' => $ticket, 'ticket_id' => $ticket_id ] ); ?>
			<?php $this->template( 'blocks/rsvp/form/submit-button' ); ?>
		<?php endif; ?>
	</div>

</form>
