<?php
/**
 * Tickets Commerce: Free Gateway Checkout Button.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/gateway/free/button.php
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
<button id="tec-tc-gateway-free-checkout-button" class="tribe-common-c-btn tribe-tickets__commerce-checkout-form-submit-button">
	<div class="spinner hidden" id="spinner"></div>
	<span id="button-text">
		<?php
			printf(
				// Translators: %1$s: Plural `Tickets` label.
				esc_html__( 'Get %1$s', 'event-tickets' ),
				tribe_get_ticket_label_plural( 'tickets_commerce_checkout_title' ) // phpcs:ignore
			);
			?>
	</span>
</button>
