<?php
/**
 * Tickets Commerce: Checkout Page Purchaser Info Address Fields
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/checkout/purchaser-info/address.php
 *
 * See more documentation about our views templating system.
 *
 * @link    https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since   TBD
 *
 * @version TBD
 *
 * @var \Tribe__Template $this [Global] Template object.
 */

$label_classes = [
	'tribe-tickets__form-field-label',
	'tribe-tickets__commerce-checkout-purchaser-info-address-field-label',
];

$field_classes = [
	'tribe-tickets__commerce-checkout-purchaser-info-form-field',
	'tribe-tickets__commerce-checkout-purchaser-info-form-field-address',
	'tribe-common-form-control-text__input',
	'tribe-tickets__form-field-input',
];
?>
<div class="tribe-tickets__commerce-checkout-purchaser-info-field tribe-tickets__form-field tribe-tickets__form-field--text">
	<label for="tec-tc-purchaser-address1" <?php tribe_classes( $label_classes ); ?>>
		<?php esc_html_e( 'Address Line 1:', 'event-tickets' ); ?>
	</label>
	<div class="tribe-tickets__form-field-input-wrapper">
		<input
			type="text"
			id="tec-tc-purchaser-address1"
			name="purchaser-address1"
			autocomplete="off"
			<?php tribe_classes( $field_classes ); ?>
			required
		/>
	</div>
</div>
<div class="tribe-tickets__commerce-checkout-purchaser-info-field tribe-tickets__form-field tribe-tickets__form-field--text">
	<label for="tec-tc-purchaser-address2" <?php tribe_classes( $label_classes ); ?>>
		<?php esc_html_e( 'Address Line 2 (optional):', 'event-tickets' ); ?>
	</label>
	<div class="tribe-tickets__form-field-input-wrapper">
		<input
			type="text"
			id="tec-tc-purchaser-address2"
			name="purchaser-address2"
			autocomplete="off"
			<?php tribe_classes( $field_classes ); ?>
		/>
	</div>
</div>
