<?php
/**
 * Tickets Commerce: Stripe Checkout Form
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/gateway/stripe/container.php
 *
 * See more documentation about our views templating system.
 *
 * @link    https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @todo FrontEnd needs to revisit all of these templates to make sure we're not missing anything
 *
 * @since 5.3.0
 *
 * @version 5.3.0
 *
 * @var bool $must_login      [Global] Whether login is required to buy tickets or not.
 * @var bool $payment_element [Global] Whether to load the Stripe Payment Element.
 */

if ( ! empty( $must_login ) ) {
	return;
}
?>
<div class="tribe-tickets__commerce-checkout-gateway tribe-tickets__commerce-checkout-stripe">
	<form id="payment-form">

		<?php $this->template( 'gateway/stripe/payment-element' ); ?>

		<?php $this->template( 'gateway/stripe/card-element' ); ?>

	</form>
</div>
