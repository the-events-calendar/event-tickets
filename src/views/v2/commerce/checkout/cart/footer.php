<?php
/**
 * Tickets Commerce: Checkout Cart Footer
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/checkout/cart/footer.php
 *
 * See more documentation about our views templating system.
 *
 * @link    https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since   TBD
 *
 * @version TBD
 *
 * @var \Tribe__Template $this                  [Global] Template object.
 * @var Module           $provider              [Global] The tickets provider instance.
 * @var string           $provider_id           [Global] The tickets provider class name.
 * @var array[]          $items                 [Global] List of Items on the cart to be checked out.
 * @var string           $paypal_attribution_id [Global] What is our PayPal Attribution ID.
 */

$classes = [
	'tribe-tickets__commerce-checkout-cart-footer',
	'tribe-common-b1',
]
?>
<div <?php tribe_classes( $classes ); ?>>
	<div class="tribe-tickets__commerce-checkout-footer-quantity">
		<span class="tribe-tickets__commerce-checkout-footer-quantity-label">
			<?php esc_html_e( 'Quantity: ', 'event-tickets' ); ?>
		</span>
		<span class="tribe-tickets__commerce-checkout-footer-quantity-number">
			<?php echo array_sum( wp_list_pluck( $items, 'quantity' ) ); ?>
		</span>
	</div>

	<div class="tribe-tickets__commerce-checkout-footer-total">
		<span class="tribe-tickets__commerce-checkout-footer-total-label">
			<?php esc_html_e( 'Total: ', 'event-tickets' ); ?>
		</span>
		<span class="tribe-tickets__commerce-checkout-footer-total-wrap">
			<?php echo esc_html( $total_value ); ?>
		</div>
</div>
