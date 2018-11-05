<?php
/**
 * This template renders the attendee registration back to cart button
 *
 * @version TBD
 *
 */
$cart_url = $this->get_cart_url( $event_id );
?>
<?php if ( ! empty( $cart_url ) ): ?>
	<a href="<?php echo esc_url( $cart_url ); ?>">
		<?php esc_html_e( 'Back to cart', 'event-tickets' ); ?>
	</a>
<?php endif; ?>