<?php
/**
 * My Tickets: Attendee Label
 *
 * Override this template in your own theme by creating a file at
 * [your-theme]/tribe/tickets-seating/tickets-block.php
 *
 * @since   TBD
 *
 * @version TBD
 */
?>

<div class="tribe-common event-tickets tribe-tickets__tickets-wrapper">
	<div class="tribe-tickets__tickets-form tec-tickets-sld__tickets-block">

		<div class="tec-tickets-seating__tickets-block__information">
			<h2 class="tribe-common-h4 tribe-common-h--alt tribe-tickets__tickets-title">
				<?php echo esc_html( tribe_get_ticket_label_plural( 'purchase-form' ) ); ?>
			</h2>
			<span>$8.00 - $10.00 </span>
			<span>|</span>
			<span>46 available</span>
		</div>

		<div class="tec-tickets-seating__tickets-block__action">
			<button
				type="submit"
				class="tribe-common-c-btn tribe-common-c-btn--small tribe-tickets__attendee-tickets-submit tribe-tickets__attendee-tickets-footer-checkout-button tribe-validation-submit"
				name="checkout-button"
			>
				<?php esc_html_e( 'Buy Now', 'event-tickets' ); ?>
			</button>
		</div>

	</div>
</div>