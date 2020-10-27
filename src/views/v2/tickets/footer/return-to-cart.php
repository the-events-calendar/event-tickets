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
 * If RSVP:
 * @var WP_Post|int                        $post_id         The post object or ID.
 * @var Tribe__Tickets__Tickets            $provider        The tickets provider class.
 * @var string                             $provider_id     The tickets provider class name.
 * @var Tribe__Tickets__Ticket_Object[]    $tickets         List of tickets.
 * @var Tribe__Tickets__Ticket_Object[]    $tickets_on_sale List of tickets on sale.
 * @var Tribe__Tickets__Commerce__Currency $currency        The Currency instance.
 * @var boolean                            $is_mini         Context of template.
 * @var Tribe__Tickets__Ticket_Object      $ticket          The ticket.
 * @var int                                $key             The ticket key.
 *
 * If Ticket, some of the above but not all.
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
<a class="tribe-common-b2 tribe-tickets__tickets-footer-back-link" href="<?php echo esc_url( $cart_url ); ?>">
	<?php esc_html_e( 'Return to Cart', 'event-tickets' ); ?>
</a>
