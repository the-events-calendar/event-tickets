<?php
/**
 * This template renders the attendee registration back to cart button
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
 * @var string $provider The commerce provider.
 */

/** @var Tribe__Tickets__Attendee_Registration__View $ar_view */
$ar_view      = tribe( 'tickets.attendee_registration.view' );
$cart_url     = $ar_view->get_cart_url( $provider );
$provider_obj = $ar_view->get_cart_provider( $provider );

$checkout_url = method_exists( $provider_obj, 'get_checkout_url' ) ? $provider_obj->get_checkout_url() : '';

// If the cart and checkout urls are the same, don't display.
if ( strtok( $cart_url, '?' ) === strtok( $checkout_url, '?' ) ) {
	return;
}

// Bail if the "Cart URL" is empty.
if ( empty( $cart_url ) ) {
	return;
}
?>
<a
	href="<?php echo esc_url( $cart_url ); ?>"
	class="tribe-tickets__registration__back__to__cart"
><?php esc_html_e( 'Back to cart', 'event-tickets' ); ?></a>
