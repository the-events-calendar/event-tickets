<?php
/**
 * This template renders the attendee registration checkout button
 *
 * @version 4.9
 *
 */
if ( ! $checkout_url ) {
	return;
}
 ?>
<?php if ( ! $cart_has_required_meta || $is_meta_up_to_date ) : ?>
	<form
		class="tribe-block__tickets__registration__checkout"
		action="<?php echo esc_url( $checkout_url ); ?>"
		method="post"
	>
		<input type="hidden" name="tribe_tickets_checkout" value="1" />
		<button type="submit" class="alignright button-primary tribe-block__tickets__registration__checkout__submit">
			<?php esc_html_e( 'Checkout', 'event-tickets' ); ?>
		</button>
	</form>
<?php endif;
