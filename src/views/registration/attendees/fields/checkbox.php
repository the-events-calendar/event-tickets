<?php
/**
 * This template renders the Checkbox
 *
 * @version TBD
 *
 */
$required      = isset( $field->required ) && 'on' === $field->required ? true : false;
$field         = (array) $field;
$attendee_id   = null;
$value         = '';
$is_restricted = false;
$options       = Tribe__Utils__Array::get( $field, array( 'extra', 'options' ), null );

if ( ! is_array( $value ) ) {
	$value = array();
}

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
		foreach ( $options as $option ) {
			$option_slug = sanitize_title( $option );
			$field_slug  = $field['slug'];
			$option_id   = "tribe-tickets-meta_{$field_slug}" . ( $attendee_id ? '_' . $attendee_id : '' ) . "_{$option_slug}";
			$slug        = $field_slug . '_' . $option_slug;
			?>
			<label for="<?php echo esc_attr( $option_id ); ?>" class="tribe-tickets-meta-field-header">
				<input
					type="checkbox"
					id="<?php echo esc_attr( $option_id ); ?>"
					class="ticket-meta"
					name="tribe-tickets-meta[<?php echo esc_attr( $attendee_id ) ?>][<?php echo esc_attr( $slug ); ?>]"
					value="<?php echo esc_attr( $option ); ?>"
					<?php checked( true, in_array( $slug, $value ) ); ?>
					<?php disabled( $is_restricted ); ?>>
				<span class="tribe-tickets-meta-option-label">
					<?php echo wp_kses_post( $option ); ?>
				</span>
			</label>
			<?php
		}
		?>
	</div>
</div>
