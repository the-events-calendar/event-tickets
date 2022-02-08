<?php
/**
 * Tickets Commerce: Card Element Checkout for Stripe
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/gateway/stripe/card-element.php
 *
 * See more documentation about our views templating system.
 *
 * @link    https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since   TBD
 *
 * @version TBD
 * @var bool $must_login [Global] Whether login is required to buy tickets or not.
 * @var bool $payment_element [Global] Whether to load the Stripe Payment Element.
 */

if ( $must_login || $payment_element ) {
	return;
}
?>
<div id="tec-tc-gateway-stripe-card-element" class="tribe-tickets__commerce-checkout-stripe-card-element">
	<span id="tec-tc-gateway-stripe-card-number"></span>
	<span id="tec-tc-gateway-stripe-card-expiry"></span>
	<span id="tec-tc-gateway-stripe-card-cvc"></span>
	<span id="tec-tc-gateway-stripe-card-zip">
		<input
			placeholder="<?php esc_attr_e( 'Zip Code', 'event-tickets' ); ?>"
		/>
	</span>
</div>

<div id="tec-tc-gateway-stripe-errors" role="alert"></div>

<button
	id="tec-tc-gateway-stripe-checkout-button"
	class="tribe-common-c-btn"
>
	<?php
	printf(
		// Translators: %1$s: Plural `Tickets` label.
		esc_html__( 'Purchase %1$s', 'event-tickets' ),
		tribe_get_ticket_label_plural( 'tickets_commerce_checkout_title' ) // phpcs:ignore
	);
	?>
</button>
