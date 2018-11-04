<?php
/**
 * This template renders the attendee registration checkout button
 *
 * @version TBD
 *
 */
?>
<?php if ( ! empty( $checkout_url ) ): ?>
	<form action="<?php echo esc_url( $checkout_url ); ?>" method="get">
		<button type="submit" class="alignright button-primary"><?php esc_html_e( 'Checkout', 'event-tickets' ); ?></button>
	</form>
<?php endif; ?>