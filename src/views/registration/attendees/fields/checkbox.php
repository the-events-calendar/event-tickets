<?php
/**
 * This template renders the Checkbox
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/registration/attendees/fields/checkbox.php
 *
 * @version 4.9
 *
 */
$required      = isset( $field->required ) && 'on' === $field->required ? true : false;
$field         = (array) $field;
$attendee_id   = $key;
$options       = Tribe__Utils__Array::get( $field, array( 'extra', 'options' ), null );

if ( ! $options ) {
	return;
}
?>
<div class="tribe-field tribe-block__tickets__item__attendee__field__checkbox <?php echo $required ? 'tribe-tickets-meta-required' : ''; ?>">
	<header class="tribe-tickets-meta-label">
		<h3><?php echo wp_kses_post( $field['label'] ); ?></h3>
	</header>
	<div class="tribe-options">
		<?php
		foreach ( $options as $option ) :

			$option_slug = sanitize_title( $option );
			$field_slug  = $field['slug'];
			$option_id   = "tribe-tickets-meta_{$field_slug}" . ( $attendee_id ? '_' . $attendee_id : '' ) . "_{$option_slug}";
			$slug        = $field_slug . '_' . $option_slug;
			$value       = isset( $saved_meta[ $ticket->ID ][ $attendee_id ][ $slug ] ) ? $saved_meta[ $ticket->ID ][ $attendee_id ][ $slug ] : false;
			?>
			<label for="<?php echo esc_attr( $option_id ); ?>" class="tribe-tickets-meta-field-header">
				<input
					type="checkbox"
					id="<?php echo esc_attr( $option_id ); ?>"
					class="ticket-meta"
					name="<?php echo 'tribe-tickets-meta[' . esc_attr( $ticket->ID ) . '][' . esc_attr( $attendee_id ) . '][' . esc_attr( $slug ) . ']'; ?>"
					value="<?php echo esc_attr( $option ); ?>"
					<?php checked( $option, $value ); ?>
				/>
				<span class="tribe-tickets-meta-option-label">
					<?php echo wp_kses_post( $option ); ?>
				</span>
			</label>
		<?php endforeach; ?>
	</div>
</div>
