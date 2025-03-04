<?php
/**
 * Tickets Commerce: Checkout Page Purchaser Info Country Field
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/checkout/purchaser-info/country.php
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
	'tribe-tickets__commerce-checkout-purchaser-info-country-field-label',
];

$field_classes = [
	'tribe-common-b2',
	'tribe-tickets__commerce-checkout-purchaser-info-form-field',
	'tribe-tickets__commerce-checkout-purchaser-info-form-field-country',
	'tribe-common-form-control-text__input',
	'tribe-tickets__form-field-input',
];

$options   = '<option value="">' . esc_html__( 'Select a country', 'event-tickets' ) . '</option>';
$countries = tribe( 'languages.locations' )->get_countries();
foreach ( $countries as $key => $value ) {
	$options .= '<option ' . selected( $key, $field['value'], false ) . ' value="' . esc_attr( $key ) . '">' . esc_html( $value ) . '</option>';
}

?>
<div class="tribe-tickets__commerce-checkout-purchaser-info-field tribe-tickets__form-field tribe-tickets__form-field--country">
	<label for="tec-tc-purchaser-country" <?php tribe_classes( $label_classes ); ?>>
		<?php echo esc_html( $field['label'] ); ?>
	</label>

	<div class="tribe-tickets__form-field-input-wrapper">
		<select
			name="purchaser-country"
			id="tec-tc-purchaser-country"
			autocomplete="off"
			<?php tribe_classes( $field_classes ); ?>
			required
		>
			<?php echo $options; // phpcs:ignore WordPress.Security.EscapeOutput,StellarWP.XSS.EscapeOutput ?>
		</select>
		<div class="tribe-common-b3 tribe-tickets__form-field-description tribe-common-a11y-hidden error">
			<?php esc_html_e( 'Your country is required', 'event-tickets' ); ?>
		</div>
	</div>
</div>
