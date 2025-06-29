<?php
/**
 * This template renders the Checkbox
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/registration/attendees/fields/checkbox.php
 *
 * @since 4.9
 * @since 4.10.1 Update template paths to add the "registration/" prefix
 * @since 4.10.2 Use md5() for field name slugs
 * @version 4.11.0
 *
 */
$required    = isset( $field->required ) && 'on' === $field->required ? true : false;
$field       = (array) $field;
$attendee_id = $key;
$options     = Tribe__Utils__Array::get( $field, array( 'extra', 'options' ), null );
$field_name  = 'tribe-tickets-meta[' . $ticket->ID . '][' . $attendee_id . ']';
$disabled    = false;

if ( ! $options ) {
	return;
}
?>
<div class="tribe-field tribe-tickets-meta-fieldset tribe-tickets-meta-fieldset__checkbox-radio <?php echo $required ? 'tribe-tickets-meta-required' : ''; ?>">
	<header class="tribe-tickets-meta-label">
		<h3 class="tribe-common-b1 tribe-common-b2--min-medium"><?php echo wp_kses_post( $field['label'] ); ?><?php tribe_required_label( $required ); ?></h3>
	</header>

	<div class="tribe-common-form-control-checkbox-radio-group">
		<?php
		foreach ( $options as $option ) :
			$option_slug = md5( sanitize_title( $option ) );
			$field_slug  = $field['slug'];
			$option_id   = "tribe-tickets-meta_{$field_slug}" . ( $attendee_id ? '_' . $attendee_id : '' ) . "_{$option_slug}";
			$slug        = $field_slug . '_' . $option_slug;
			$value       = isset( $saved_meta[ $ticket->ID ][ $attendee_id ][ $slug ] ) ? $saved_meta[ $ticket->ID ][ $attendee_id ][ $slug ] : [];
		?>

		<div class="tribe-common-form-control-checkbox">
			<label
				class="tribe-common-form-control-checkbox__label"
				for="<?php echo esc_attr( $option_id ); ?>"
			>
				<input
					class="tribe-common-form-control-checkbox__input"
					id="<?php echo esc_attr( $option_id ); ?>"
					name="tribe-tickets-meta[<?php echo esc_attr( $attendee_id ); ?>][<?php echo esc_attr( $slug ); ?>]"
					type="checkbox"
					value="<?php echo esc_attr( $option ); ?>"
					<?php checked( true, in_array( $slug, $value ) ); ?>
					<?php tribe_disabled( $disabled ); ?>
					<?php echo $required ? 'required' : ''; ?>
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
