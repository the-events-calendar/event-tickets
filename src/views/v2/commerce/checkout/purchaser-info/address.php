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
 * @since 5.13.1
 *
 * @version 5.13.1
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
		<?php esc_html_e( 'Address line 1', 'event-tickets' ); ?>
	</label>
	<div class="tribe-tickets__form-field-input-wrapper">
		<input
			type="text"
			id="tec-tc-purchaser-address1"
			name="purchaser-address1"
			autocomplete="off"
			placeholder="<?php esc_attr_e( 'Street address', 'event-tickets' ); ?>"
			<?php tribe_classes( $field_classes ); ?>
			required
			<?php echo ! empty( $field['value']['line1'] ) ? 'value="' . esc_attr( $field['value']['line1'] ) . '"' : ''; ?>
		/>
		<div class="tribe-common-b3 tribe-tickets__form-field-description tribe-common-a11y-hidden error">
			<?php esc_html_e( 'Your address is required', 'event-tickets' ); ?>
		</div>
	</div>
</div>
<div class="tribe-tickets__commerce-checkout-purchaser-info-field tribe-tickets__form-field tribe-tickets__form-field--text">
	<label for="tec-tc-purchaser-address2" <?php tribe_classes( $label_classes ); ?>>
		<?php esc_html_e( 'Address line 2', 'event-tickets' ); ?>
	</label>
	<div class="tribe-tickets__form-field-input-wrapper">
		<input
			type="text"
			id="tec-tc-purchaser-address2"
			name="purchaser-address2"
			placeholder="<?php esc_attr_e( 'Apt., suite, unit number, etc (optional)', 'event-tickets' ); ?>"
			autocomplete="off"
			<?php tribe_classes( $field_classes ); ?>
			<?php echo ! empty( $field['value']['line2'] ) ? 'value="' . esc_attr( $field['value']['line2'] ) . '"' : ''; ?>
		/>
		<div class="tribe-common-b3 tribe-tickets__form-field-description tribe-common-a11y-hidden error"></div>
	</div>
</div>
