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
<form id="payment-form">
	<div id="card-element">
		<!-- Elements will create input elements here -->
	</div>

	<!-- We'll put the error messages in this element -->
	<div id="card-errors" role="alert"></div>

	<button id="submit">Submit Payment</button>
</form>