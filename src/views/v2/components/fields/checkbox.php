<?php
/**
 * This template renders the Checkbox field.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/components/fields/checkbox.php
 *
 * @since 4.12.3
 *
 * @version 4.12.3
 *
 * @see Tribe__Tickets_Plus__Meta__Field__Checkbox
 */

$field_name = tribe_tickets_ar_field_name( $ticket->ID, $field->slug );
$required   = tribe_tickets_ar_field_is_required( $field );
$field      = (array) $field;
$options    = Tribe__Utils__Array::get( $field, [ 'extra', 'options' ], null );
$disabled   = false;

if ( ! $options ) {
	return;
}

$classes = [
	'tribe-tickets__form-field',
	'tribe-tickets__form-field--required' => $required,
];

?>
<div <?php tribe_classes( $classes ); ?>>
	<header class="tribe-tickets__form-field-label">
		<h3 class="tribe-common-b1 tribe-common-b2--min-medium">
			<?php echo wp_kses_post( $field['label'] ); ?><?php tribe_required_label( $required ); ?>
		</h3>
	</header>

	<div class="tribe-common-form-control-checkbox-radio-group">
		<?php
		foreach ( $options as $option ) :
			$option_slug = md5( sanitize_title( $option ) );
			$field_slug  = $field['slug'];
			$option_id   = tribe_tickets_ar_field_id( $ticket->ID, $field_slug, $option_slug );
			$slug        = $field_slug . '_' . $option_slug;
			$field_name  = tribe_tickets_ar_field_name( $ticket->ID, $field_slug, $option_slug );
			$value       = [];
			?>

		<div class="tribe-common-form-control-checkbox">
			<label
				class="tribe-common-form-control-checkbox__label"
				for="<?php echo esc_attr( $option_id ); ?>"
			>
				<input
					class="tribe-common-form-control-checkbox__input tribe-tickets__form-field-input"
					id="<?php echo esc_attr( $option_id ); ?>"
					name="<?php echo esc_attr( $field_name ); ?>"
					type="checkbox"
					value="<?php echo esc_attr( $option ); ?>"
					<?php checked( true, in_array( $slug, $value, true ) ); ?>
					<?php tribe_disabled( $disabled ); ?>
					<?php tribe_required( $required ); ?>
				/>
				<?php echo wp_kses_post( $option ); ?>
			</label>
		</div>
		<?php endforeach; ?>
	</div>
	<input
		type="hidden"
		name="<?php echo esc_attr( $field_name . '[0]' ); ?>"
		value=""
	>
</div>
