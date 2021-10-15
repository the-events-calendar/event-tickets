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
<div class="tec-tickets__commerce-advanced-payments-container">
	<form class="tec-tickets__commerce-advanced-payments-form">

		<div class="">
			<label for="tec-tc-card-number">
				<?php esc_html_e( 'Card Number', 'event-tickets' ); ?>
			</label>
			<div id="tec-tc-card-number" class="card_field"></div>
		</div>

		<div class="">
			<label for="tec-tc-expiration-date">
				<?php esc_html_e( 'Expiration Date', 'event-tickets' ); ?>
			</label>
			<div id="tec-tc-expiration-date" class="card_field"></div>
		</div>

		<div class="">
			<label for="tec-tc-cvv">
				<?php esc_html_e( 'CVV', 'event-tickets' ); ?>
			</label>
			<div id="tec-tc-cvv" class="card_field"></div>
		</div>

		<div class="">
			<label for="tec-tc-card-holder-name">
				<?php esc_html_e( 'Name on Card', 'event-tickets' ); ?>
			</label>
			<input
				type="text"
				id="tec-tc-card-holder-name"
				name="card-holder-name"
				autocomplete="off"
				placeholder="card holder name"
			/>
		</div>

		<button value="submit" id="submit" class="btn">
			<?php esc_html_e( 'Purchase Tickets', 'event-tickets' ); ?>
		</button>
	</form>
</div>