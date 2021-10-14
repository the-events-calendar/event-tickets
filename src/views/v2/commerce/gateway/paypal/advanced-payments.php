<?php
/**
 * Tickets Commerce: Checkout Advanced Payments for PayPal
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/gateway/paypal/advanced-payments.php
 *
 * See more documentation about our views templating system.
 *
 * @link    https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since   TBD
 *
 * @version TBD
 *
 * @var bool $must_login              [Global] Whether login is required to buy tickets or not.
 * @var bool $support_custom_payments [Global] Determines if this site supports custom payments.
 */


if ( $must_login ) {
	return;
}

if ( ! $supports_custom_payments ) {
	return;
}
?>

<!-- Advanced credit and debit card payments form -->
<div class="card_container">
	<form id="card-form">

		<label for="card-number">Card Number</label>
		<div id="card-number" class="card_field"></div>

		<div>
			<label for="expiration-date">Expiration Date</label>
			<div id="expiration-date" class="card_field"></div>
		</div>

		<div>
			<label for="cvv">CVV</label>
			<div id="cvv" class="card_field"></div>
		</div>
		<label for="card-holder-name">Name on Card</label>
		<input type="text" id="card-holder-name" name="card-holder-name" autocomplete="off" placeholder="card holder name"/>

		<button value="submit" id="submit" class="btn">Pay</button>
	</form>
</div>