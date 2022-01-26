<?php
/**
 * Tickets Commerce: Checkout Buttons for Stripe
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/gateway/stripe/buttons.php
 *
 * See more documentation about our views templating system.
 *
 * @link    https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since   TBD
 *
 * @version TBD
 * @var bool $must_login [Global] Whether login is required to buy tickets or not.
 */

if ( $must_login ) {
	return;
}
?>
<div id="tec-tc-gateway-stripe-payment-element">
	<!--Stripe.js injects the Payment Element-->
</div>
<button id="tec-tc-gateway-stripe-checkout-button">
	<div class="spinner hidden" id="spinner"></div>
	<span id="button-text">Pay now</span>
</button>
<div id="tec-tc-gateway-stripe-payment-message" class="hidden"></div>