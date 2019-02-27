<?php
/**
 * This template renders a Single Ticket content
 * composed by Title and Description currently
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/registration/attendees/fields/text.php
 *
 * @since 4.9
 * @since 4.10.1 Update template paths to add the "registration/" prefix
 * @version 4.10.1
 *
 */
$attendee_id   = $key;
$required      = isset( $field->required ) && 'on' === $field->required ? true : false;
$option_id     = "tribe-tickets-meta_{$field->slug}_{$ticket->ID}" . ( $attendee_id ? '_' . $attendee_id : '' );
$field         = (array) $field;
$multiline     = isset( $field['extra'] ) && isset( $field['extra']['multiline'] ) ? $field['extra']['multiline'] : '';
$field_name    = 'tribe-tickets-meta[' . $ticket->ID . '][' . $attendee_id . '][' . esc_attr( $field['slug'] ) . ']';

?>
<div class="tribe-field tribe-block__tickets__item__attendee__field__text <?php echo $required ? 'tribe-tickets-meta-required' : ''; ?>">
	<label for="<?php echo esc_attr( $option_id ); ?>"><?php echo wp_kses_post( $field['label'] ); ?></label>
	<?php if ( $multiline ) : ?>
		<textarea
			id="<?php echo esc_attr( $option_id ); ?>"
			name="<?php echo esc_attr( $field_name ); ?>"
			<?php echo $required ? 'required' : ''; ?>
		><?php echo esc_textarea( $value ); ?></textarea>
	<?php else : ?>
		<input
			type="text"
			id="<?php echo esc_attr( $option_id ); ?>"
			name="<?php echo esc_attr( $field_name ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			<?php echo $required ? 'required' : ''; ?>
		/>
	<?php endif; ?>
</div>
