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
 * @var string                  $cart_url     The provider cart URL.
 * @var string                  $checkout_url The provider checkout URL.
 * @var bool                    $is_mini      True if in "mini cart" context.
 */


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
