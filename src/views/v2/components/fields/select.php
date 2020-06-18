<?php
/**
 * The template for the select input.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/components/fields/select.php
 *
 * @since TBD
 *
 * @version TBD
 *
 * @see Tribe__Tickets_Plus__Meta__Field__Select
 */

$field_name = tribe_tickets_ar_field_name( $ticket->ID, $field->slug );
$field_id   = tribe_tickets_ar_field_id( $ticket->ID, $field->slug );
$required   = tribe_tickets_ar_field_is_required( $field );
$field      = (array) $field;
$disabled   = false;
$slug       = $field['slug'];
$options    = null;

if ( isset( $field['extra'] ) && ! empty( $field['extra']['options'] ) ) {
	$options = $field['extra']['options'];
}

if ( ! $options ) {
	return;
}

$classes = [
	'tribe-tickets__form-field',
	'tribe-tickets__form-field--required' => $required,
];

?>
<div <?php tribe_classes( $classes ); ?>>
	<label
		class="tribe-common-b1 tribe-common-b2--min-medium tribe-tickets__form-field-label"
		for="<?php echo esc_attr( $field_id ); ?>"
		><?php echo wp_kses_post( $field['label'] ); ?><?php tribe_required_label( $required ); ?>
	</label>
	<select
		<?php tribe_disabled( $disabled ); ?>
		id="<?php echo esc_attr( $field_id ); ?>"
		class="tribe-common-form-control-select__input tribe-tickets__form-field-input tribe-common-b2"
		name="<?php echo esc_attr( $field_name ); ?>"
		<?php tribe_required( $required ); ?>
		>
		<option value=""><?php esc_html_e( 'Select an option', 'event-tickets' ); ?></option>
		<?php foreach ( $options as $option => $label ) : ?>
			<option <?php selected( $label, $value ); ?> value="<?php echo esc_attr( $label ); ?>"><?php echo esc_html( $label ); ?></option>
		<?php endforeach; ?>
	</select>
</div>
