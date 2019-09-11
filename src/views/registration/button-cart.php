<?php
/**
 * This template renders the attendee registration back to cart button
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/registration/button-cart.php
 *
 * @since 4.9
 * @since 4.10.1 Update template paths to add the "registration/" prefix
 * @version 4.10.1
 *
 *
 * @var Tribe__Tickets__Attendee_Registration__View $this
 */
$cart_url = $this->get_cart_url( $provider );
?>
<?php if ( $cart_url ) : ?>
	<a
		href="<?php echo esc_url( $cart_url ); ?>"
		class="tribe-block__tickets__registration__back__to__cart"
	>
		<?php esc_html_e( 'Back to cart', 'event-tickets' ); ?>
	</a>
<?php endif;
