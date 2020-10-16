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
 * @link    https://m.tri.be/1amp Help article for RSVP & Ticket template files.
 *
 * @since   TBD
 *
 * @version TBD
 *
 * @var Tribe__Tickets__Tickets $provider    The tickets provider instance.
 * @var string                  $provider_id The tickets provider class name.
 * @var WP_Post|int             $post_id     The post object or ID.
 * @var int                     $key         The ticket key.
 * @var bool                    $is_mini     True if in "mini cart" context.
 */

if ( method_exists( $provider, 'get_cart_url' ) ) {
	$cart_url = $provider->get_cart_url();
} else {
	$cart_url = '';
}

if ( method_exists( $provider, 'get_checkout_url' ) ) {
	$checkout_url = $provider->get_checkout_url();
} else {
	$checkout_url = '';
}

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
