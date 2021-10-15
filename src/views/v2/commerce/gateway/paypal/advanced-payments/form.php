<?php
/**
 * Tickets Commerce: Checkout Advanced Payments for PayPal - Form
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/gateway/paypal/advanced-payments/form.php
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
<form class="tribe-tickets__commerce-checkout-paypal-advanced-payments-form">

	<?php $this->template( 'gateway/paypal/advanced-payments/fields/card-number' ); ?>

	<?php $this->template( 'gateway/paypal/advanced-payments/fields/expiration-date' ); ?>

	<?php $this->template( 'gateway/paypal/advanced-payments/fields/card-name' ); ?>

	<?php $this->template( 'gateway/paypal/advanced-payments/fields/cvv' ); ?>

	<?php $this->template( 'gateway/paypal/advanced-payments/fields/submit' ); ?>

</form>
