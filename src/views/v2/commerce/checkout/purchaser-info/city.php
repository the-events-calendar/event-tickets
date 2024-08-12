<?php
/**
 * Tickets Commerce: Checkout Page Purchaser Info City Field
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/checkout/purchaser-info/city.php
 *
 * See more documentation about our views templating system.
 *
 * @link    https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since   5.13.1
 *
 * @version 5.13.1
 *
 * @var \Tribe__Template $this [Global] Template object.
 */

$label_classes = [
	'tribe-tickets__form-field-label',
	'tribe-tickets__commerce-checkout-purchaser-info-city-field-label',
];

$field_classes = [
	'tribe-common-b2',
	'tribe-tickets__commerce-checkout-purchaser-info-form-field',
	'tribe-tickets__commerce-checkout-purchaser-info-form-field-city',
	'tribe-common-form-control-text__input',
	'tribe-tickets__form-field-input',
];
?>
<div class="tribe-tickets__commerce-checkout-purchaser-info-field tribe-tickets__form-field tribe-tickets__form-field--city">
	<label for="tec-tc-purchaser-city" <?php tribe_classes( $label_classes ); ?>>
		<?php esc_html_e( 'City', 'event-tickets' ); ?>
	</label>

	<div class="tribe-tickets__form-field-input-wrapper">
		<input
			type="text"
			id="tec-tc-purchaser-city"
			name="purchaser-city"
			autocomplete="off"
			<?php tribe_classes( $field_classes ); ?>
			required
		/>
		<div class="tribe-common-b3 tribe-tickets__form-field-description tribe-common-a11y-hidden error">
			<?php esc_html_e( 'Your city is required', 'event-tickets' ); ?>
		</div>
	</div>
</div>
