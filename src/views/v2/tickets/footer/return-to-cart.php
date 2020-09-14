<?php
/**
 * Block: Tickets
 * Footer "Return to cart"
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/tickets/footer/return-to-cart.php
 *
 * See more documentation about our views templating system.
 *
 * @link http://m.tri.be/1amp
 *
 * @since TBD
 * @version TBD
 *
 * @var Tribe__Tickets__Tickets $provider    The tickets provider class.
 * @var string                  $provider_id The tickets provider class name.
 */

$cart_url     = method_exists( $provider, 'get_cart_url' ) ? $provider->get_cart_url() : '';
$checkout_url = method_exists( $provider, 'get_checkout_url' ) ? $provider->get_checkout_url() : '';

if (
	! $is_mini
	|| strtok( $cart_url, '?' ) === strtok( $checkout_url, '?' )
) {
	return;
}

?>
<a class="tribe-common-b2 tribe-tickets__footer__back-link" href="<?php echo esc_url( $cart_url ); ?>">
	<?php esc_html_e( 'Return to Cart', 'event-tickets' ); ?>
</a>
