<?php
/**
 * Tickets Commerce: Payment Element Checkout for Stripe
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/gateway/stripe/payment-element.php
 *
 * See more documentation about our views templating system.
 *
 * @link    https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since   TBD
 *
 * @version TBD
 * @var bool $must_login      [Global] Whether login is required to buy tickets or not.
 * @var bool $payment_element [Global] Whether to load the Stripe Payment Element.
 */

if ( $must_login || ! $payment_element ) {
	return;
}
?>
<div id="tec-tc-gateway-stripe-payment-element" class="tribe-tickets__commerce-checkout-stripe-payment-element"></div>
<button id="tec-tc-gateway-stripe-checkout-button" class="tribe-common-c-btn">
	<div class="spinner hidden" id="spinner"></div>
	<span id="button-text"><?php esc_html_e( 'Pay now', 'event-tickets' ); ?></span>
</button>
<div id="tec-tc-gateway-stripe-payment-message" class="hidden"></div>
<div id="tec-tc-gateway-stripe-errors" role="alert"></div>
