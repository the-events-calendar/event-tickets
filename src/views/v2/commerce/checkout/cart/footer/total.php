<?php
/**
 * Tickets Commerce: Checkout Cart Footer Total
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/checkout/cart/footer/total.php
 *
 * See more documentation about our views templating system.
 *
 * @link    https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since   5.1.9
 *
 * @version 5.1.9
 *
 * @var \Tribe__Template $this                  [Global] Template object.
 * @var Module           $provider              [Global] The tickets provider instance.
 * @var string           $provider_id           [Global] The tickets provider class name.
 * @var array[]          $items                 [Global] List of Items on the cart to be checked out.
 * @var string           $paypal_attribution_id [Global] What is our PayPal Attribution ID.
 */

?>
<div class="tribe-tickets__commerce-checkout-cart-footer-total">
	<?php
	echo wp_kses_post(
		sprintf(
			// Translators: %1$s: Opening span for "Total:" string; %2$s: Closing span for "Total:" string; %3$s: Opening span for the total value; %4$s: The total value; %5$s: Closing span for the total value.
			__( '%1$sTotal: %2$s%3$s%4$s%5$s', 'event-tickets' ),
			'<span class="tribe-tickets__commerce-checkout-cart-footer-total-label">',
			'</span>',
			'<span class="tribe-tickets__commerce-checkout-cart-footer-total-wrap">',
			$total_value,
			'</span>'
		)
	);
	?>
</div>
