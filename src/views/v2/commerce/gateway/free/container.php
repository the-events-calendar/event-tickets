<?php
/**
 * Tickets Commerce: Free Gateway Checkout Container
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/gateway/free/container.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since 5.10.0
 *
 * @version 5.10.0
 * @var bool $must_login [Global] Whether login is required to buy tickets or not.
 */

if ( ! empty( $must_login ) ) {
	return;
}
?>
<div class="tribe-tickets__commerce-checkout-gateway tribe-tickets__commerce-checkout-free">
	<form id="free-form">
		<?php $this->template( 'gateway/free/button' ); ?>
	</form>
</div>
