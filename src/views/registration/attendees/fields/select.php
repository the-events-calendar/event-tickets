<?php
/**
 * The template for the select input
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/registration/attendees/fields/select.php
 *
 * @since 4.9
 * @since TBD Update template paths to add the "registration/" prefix
 * @version TBD
 *
 */
$required      = isset( $field->required ) && 'on' === $field->required ? true : false;
$field         = (array) $field;
$attendee_id   = $key;
$is_restricted = false;
$slug          = $field['slug'];
$options       = null;
$field_name    = 'tribe-tickets-meta[' . $ticket->ID . '][' . $attendee_id . '][' . esc_attr( $field['slug'] ) . ']';

if ( isset( $field['extra'] ) && ! empty( $field['extra']['options'] ) ) {
	$options = $field['extra']['options'];
}

if ( ! $options ) {
	return;
}

$option_id = "tribe-tickets-meta_{$slug}_{$ticket->ID}" . ( $attendee_id ? '_' . $attendee_id : '' );
?>
<div class="tribe-field tribe-block__tickets__item__attendee__field__select <?php echo $required ? 'tribe-tickets-meta-required' : ''; ?>">
	<label for="<?php echo esc_attr( $option_id ); ?>"><?php echo wp_kses_post( $field['label'] ); ?></label>
	<select
		<?php disabled( $is_restricted ); ?>
		id="<?php echo esc_attr( $option_id ); ?>"
		class="ticket-meta"
		name="<?php echo esc_attr( $field_name ); ?>"
		<?php echo $required ? 'required' : ''; ?>>
		<option value=""><?php esc_html_e( 'Select an option', 'events-tickets' ); ?></option>
		<?php

			foreach ( $options as $option => $label ) :

				$option_value = md5( sanitize_title( $label ) );
		?>
			<option <?php selected( $option_value, $value ); ?> value="<?php echo esc_attr( $option_value ); ?>"><?php echo esc_html( $label ); ?></option>
		<?php endforeach; ?>
	</select>
</div>
