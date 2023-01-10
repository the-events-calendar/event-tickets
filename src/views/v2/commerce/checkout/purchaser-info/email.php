<?php
/**
 * Tickets Commerce: Checkout Page Purchaser Info Email Field
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/checkout/purchaser-info/email.php
 *
 * See more documentation about our views templating system.
 *
 * @link    https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since   5.3.0
 *
 * @version 5.3.0
 *
 * @var \Tribe__Template $this [Global] Template object.
 */

$label_classes = [
	'tribe-tickets__form-field-label',
	'tribe-tickets__commerce-checkout-purchaser-info-email-field-label',
];

$field_classes = [
	'tribe-common-b2',
	'tribe-tickets__commerce-checkout-purchaser-info-form-field',
	'tribe-tickets__commerce-checkout-purchaser-info-form-field-email',
	'tribe-common-form-control-text__input',
	'tribe-tickets__form-field-input',
];
?>
<div class="tribe-tickets__commerce-checkout-purchaser-info-field tribe-tickets__form-field tribe-tickets__form-field--email">
	<label for="tec-tc-purchaser-email" <?php tribe_classes( $label_classes ); ?>>
		<?php esc_html_e( 'Email address:', 'event-tickets' ); ?>
	</label>

	<div class="tribe-tickets__form-field-input-wrapper">
		<input
			type="email"
			id="tec-tc-purchaser-email"
			name="purchaser-email"
			autocomplete="off"
			<?php tribe_classes( $field_classes ); ?>
			required
		/>
		<div class="tribe-common-b3 tribe-tickets__form-field-description">
			<?php esc_html_e( 'Your tickets will be sent to this address', 'event-tickets' ); ?>
		</div>
	</div>
</div>
