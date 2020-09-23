<?php
/**
 * This template renders the Attendee Registration back to cart button.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/attendee-registration/button/back-to-cart.php
 *
 * See more documentation about our views templating system.
 *
 * @link http://m.tri.be/1amp
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var string $provider     The commerce provider.
 * @var string $cart_url     The cart URL.
 * @var string $checkout_url The checkout URL.
 */

// Bail if the "Cart URL" is empty.
if ( empty( $cart_url ) ) {
	return;
}

// If the cart and checkout urls are the same, don't display.
if ( strtok( $cart_url, '?' ) === strtok( $checkout_url, '?' ) ) {
	return;
}
?>
<a
	href="<?php echo esc_url( $cart_url ); ?>"
	class="tribe-tickets__registration__back__to__cart"
><?php esc_html_e( 'Back to cart', 'event-tickets' ); ?></a>
