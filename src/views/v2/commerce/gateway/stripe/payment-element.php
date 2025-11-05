<?php
/**
 * Tickets Commerce: Payment Element Checkout for Stripe
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/gateway/stripe/payment-element.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since 5.3.0
 * @since 5.25.0 Add hidden class var to avoid undefined warnings.
 * @since 5.26.7 Hide button initially and reveal via JS after billing info or immediately if no billing required.
 *
 * @version 5.26.7
 *
 * @var Checkout_Shortcode $shortcode [Global] The checkout shortcode instance.
 * @var bool               $must_login [Global] Whether login is required to buy tickets or not.
 * @var bool               $payment_element [Global] Whether to load the Stripe Payment Element.
 */

use TEC\Tickets\Commerce\Shortcodes\Checkout_Shortcode;

if ( $must_login || ! $payment_element ) {
	return;
}

?>
<div id="tec-tc-gateway-stripe-payment-element" class="tribe-tickets__commerce-checkout-stripe-payment-element"></div>
<button id="tec-tc-gateway-stripe-checkout-button" class="tribe-common-c-btn tribe-tickets__commerce-checkout-form-submit-button tribe-common-a11y-hidden">
	<div class="spinner hidden" id="spinner"></div>
	<span id="button-text">
		<?php
		printf(
			// Translators: %1$s: Plural `Tickets` label.
			esc_html__( 'Purchase %1$s', 'event-tickets' ),
			tribe_get_ticket_label_plural( 'tickets_commerce_checkout_title' ) // phpcs:ignore
		);
		?>
	</span>
</button>
<div id="tec-tc-gateway-stripe-payment-message" class="hidden"></div>
<div
	id="tec-tc-gateway-stripe-errors"
	class="tribe-common-b2"
	role="alert"></div>
