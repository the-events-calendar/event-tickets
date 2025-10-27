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
 * @since 5.3.0
 * @since 5.26.7 Hide button initially and reveal via JS after billing info or immediately if no billing required.
 *
 * @version 5.26.7
 * 
 * @var bool   $must_login        [Global] Whether login is required to buy tickets or not.
 * @var bool   $payment_element   [Global] Whether to load the Stripe Payment Element.
 * @var string $card_element_type [Global] Card element type. Either 'compact' or 'separate'.
 */

if ( $must_login || $payment_element ) {
	return;
}

$classes = [
	'tribe-tickets__commerce-checkout-stripe-card-element',
	'tribe-tickets__commerce-checkout-stripe-card-element--' . esc_attr( $card_element_type ),
];

?>
<div id="tec-tc-gateway-stripe-card-element" <?php tribe_classes( $classes ); ?>>
	<div class="tribe-tickets__commerce-checkout-stripe-card-element-row">
		<span id="tec-tc-gateway-stripe-card-number" class="tribe-tickets__commerce-checkout-stripe-card-element-number"></span>
		<span id="tec-tc-gateway-stripe-card-expiry" class="tribe-tickets__commerce-checkout-stripe-card-element-expiry"></span>
	</div>
	<div class="tribe-tickets__commerce-checkout-stripe-card-element-row">
		<span id="tec-tc-gateway-stripe-card-cvc" class="tribe-tickets__commerce-checkout-stripe-card-element-cvc"></span>
		<span id="tec-tc-gateway-stripe-card-zip" class="tribe-tickets__commerce-checkout-stripe-card-element-zip">
			<input
				class="tribe-tickets__commerce-checkout-stripe-card-element-zip-input"
				placeholder="<?php esc_attr_e( 'Zip Code', 'event-tickets' ); ?>"
			/>
		</span>
	</div>
</div>

<div
	id="tec-tc-gateway-stripe-errors"
	class="tribe-common-b2"
	role="alert"></div>

<button
	id="tec-tc-gateway-stripe-checkout-button"
	class="tribe-common-c-btn tribe-tickets__commerce-checkout-form-submit-button tribe-common-a11y-hidden"
>
	<?php
	printf(
		// Translators: %1$s: Plural `Tickets` label.
		esc_html__( 'Purchase %1$s', 'event-tickets' ),
		tribe_get_ticket_label_plural( 'tickets_commerce_checkout_title' ) // phpcs:ignore
	);
	?>
</button>
