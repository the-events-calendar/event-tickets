<?php
/**
 * This template renders a Single Ticket content
 * composed by Title and Description currently
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/components/fields/radio.php
 *
 * @since TBD
 *
 * @version TBD
 *
 * @see Tribe__Tickets_Plus__Meta__Field__Radio
 */

$field      = $this->get( 'field' );
$field_name = tribe_tickets_ar_field_name( $ticket->ID, $field->slug );
$required   = tribe_tickets_ar_field_is_required( $field );
$field      = (array) $field;
$options    = null;

if ( isset( $field['extra'] ) && ! empty( $field['extra']['options'] ) ) {
	$options = $field['extra']['options'];
}

if ( ! $options ) {
	return;
}

$value    = '';
$disabled = false;
$slug     = $field['slug'];

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
			$value       = [];
		?>

		<div class="tribe-common-form-control-radio">
			<label
				class="tribe-common-form-control-radio__label"
				for="<?php echo esc_attr( $option_id ); ?>"
			>
				<input
					class="tribe-common-form-control-radio__input tribe-tickets__form-field-input"
					id="<?php echo esc_attr( $option_id ); ?>"
					name="<?php echo esc_attr( $field_name ); ?>"
					type="radio"
					value="<?php echo esc_attr( $option ); ?>"
					<?php checked( true, in_array( $slug, $value ) ); ?>
					<?php tribe_disabled( $disabled ); ?>
					<?php tribe_required( $required ); ?>
				/>
				<?php echo wp_kses_post( $option ); ?>
			</label>
		</div>
		<?php endforeach; ?>
	</div>
</div>
