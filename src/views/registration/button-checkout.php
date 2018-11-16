<?php
/**
 * This template renders the attendee registration checkout button
 *
 * @version TBD
 *
 */
if ( ! $checkout_url ) {
	return;
}
 ?>
<?php if ( ! $cart_has_required_meta || $is_meta_up_to_date ) : ?>
	<form action="<?php echo esc_url( $checkout_url ); ?>" method="post">
		<input type="hidden" name="tribe_tickets_checkout" value="1" />
		<button type="submit" class="alignright button-primary"><?php esc_html_e( 'Checkout', 'event-tickets' ); ?></button>
	</form>
<?php endif;