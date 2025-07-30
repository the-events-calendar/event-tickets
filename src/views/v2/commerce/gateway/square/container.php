<?php
/**
 * Tickets Commerce: Square Gateway Payment Element Container
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/gateway/square/container.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since 5.24.0
 *
 * @version 5.24.0
 */

?>
<form id="tec-tc-gateway-square-form" class="tribe-tickets__commerce-checkout-square-form">
	<div class="tribe-tickets__commerce-checkout-square-card-wrapper">
		<div class="tribe-tickets__commerce-checkout-square-card-container">
			<div id="tec-tc-gateway-square-card-element">
				<!-- Square Card Element will be inserted here -->
			</div>
		</div>
		<div id="tec-tc-gateway-square-errors" role="alert"></div>
		<div id="tec-tc-gateway-square-payment-message" role="alert"></div>
	</div>

	<button style="display: none;" id="tec-tc-gateway-square-checkout-button" type="submit" class="tribe-common-c-btn tribe-tickets__commerce-checkout-form-submit-button">
		<?php esc_html_e( 'Pay Now', 'event-tickets' ); ?>
	</button>
</form>
<?php
