<?php
/**
 * Tickets Commerce: Checkout Page Purchaser Info Name Field
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/checkout/purchaser-info/name.php
 *
 * See more documentation about our views templating system.
 *
 * @link    https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since 5.3.0
 *
 * @version 5.3.0
 *
 * @var \Tribe__Template $this [Global] Template object.
 */

$label_classes = [
	'tribe-tickets__form-field-label',
	'tribe-tickets__commerce-checkout-purchaser-info-name-field-label',
];

$field_classes = [
	'tribe-tickets__commerce-checkout-purchaser-info-form-field',
	'tribe-tickets__commerce-checkout-purchaser-info-form-field-name',
	'tribe-common-form-control-text__input',
	'tribe-tickets__form-field-input',
];
?>
<div class="tribe-tickets__commerce-checkout-purchaser-info-field tribe-tickets__form-field tribe-tickets__form-field--text">
	<label for="tec-tc-purchaser-name" <?php tribe_classes( $label_classes ); ?>>
		<?php echo esc_html( $field['label'] ); ?>
	</label>
	<div class="tribe-tickets__form-field-input-wrapper">
		<input
			type="<?php echo esc_attr( $field['type'] ); ?>"
			id="tec-tc-purchaser-name"
			name="purchaser-name"
			autocomplete="off"
			placeholder="<?php esc_attr_e( 'First and last name', 'event-tickets' ); ?>"
			<?php tribe_classes( $field_classes ); ?>
			required
			<?php echo $field['value'] ? 'value="' . esc_attr( $field['value'] ) . '"' : ''; ?>
		/>
		<div class="tribe-common-b3 tribe-tickets__form-field-description tribe-common-a11y-hidden error">
			<?php esc_html_e( 'Your first and last names are required', 'event-tickets' ); ?>
		</div>
	</div>
</div>
