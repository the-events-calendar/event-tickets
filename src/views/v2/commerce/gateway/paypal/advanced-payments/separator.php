<?php
/**
 * Tickets Commerce: Checkout Advanced Payments for PayPal - Separator
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/gateway/paypal/advanced-payments/separator.php
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

?>
<div class="tribe-tickets__commerce-checkout-paypal-advanced-payments-separator">
	<div class="tribe-tickets__commerce-checkout-paypal-advanced-payments-separator-line"></div>
	<div class="tribe-common-b2 tribe-tickets__commerce-checkout-paypal-advanced-payments-separator-text">
		<?php esc_html_e( 'or pay with card', 'event-tickets' ); ?>
	</div>
	<div class="tribe-tickets__commerce-checkout-paypal-advanced-payments-separator-line"></div>
</div>
