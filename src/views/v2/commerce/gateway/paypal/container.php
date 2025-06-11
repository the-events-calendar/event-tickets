<?php
/**
 * Tickets Commerce: PayPal Checkout container
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/gateway/paypal/container.php
 *
 * See more documentation about our views templating system.
 *
 * @link    https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since 5.3.0
 *
 * @version 5.3.0
 *
 * @var bool   $must_login               [Global] Whether login is required to buy tickets or not.
 * @var bool   $supports_custom_payments [Global] Determines if this site supports custom payments.
 * @var bool   $active_custom_payments   [Global] Determines if this site supports custom payments.
 * @var string $url                      [Global] Script URL.
 * @var string $client_token             [Global] One time use client Token.
 * @var string $client_token_expires_in  [Global] How much time to when the Token in this script will take to expire.
 * @var string $attribution_id           [Global] What is our PayPal Attribution ID.
 */

if ( $must_login ) {
	return;
}

?>
<div class="tribe-tickets__commerce-checkout-gateway tribe-tickets__commerce-checkout-paypal">
	<?php $this->template( 'gateway/paypal/buttons' ); ?>
	<?php $this->template( 'gateway/paypal/advanced-payments' ); ?>
	<?php $this->template( 'gateway/paypal/checkout-script' ); ?>
</div>
