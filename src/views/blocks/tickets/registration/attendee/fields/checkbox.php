<?php
/**
 * Block: Tickets
 * Registration Attendee Fields Checkbox
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/tickets/registration/attendee/fields/checkbox.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since 4.9
 * @since 4.10.2 Use md5() for field name slugs
 * @version 4.10.2
 *
 */

$required      = isset( $field->required ) && 'on' === $field->required ? true : false;
$field         = (array) $field;
$attendee_id   = null;
$value         = '';
$is_restricted = false;
$options       = null;

if ( isset( $field['extra'] ) && ! empty( $field['extra']['options'] ) ) {
	$options = $field['extra']['options'];
}

if ( ! is_array( $value ) ) {
	$value = array();
}

if ( ! $options ) {
	return;
}
?>
<div
	class="tribe-field tribe-block__tickets__item__attendee__field__checkbox <?php echo $required ? 'tribe-tickets-meta-required' : ''; ?>"
>
	<header class="tribe-tickets-meta-label">
		<h3><?php echo wp_kses_post( $field['label'] ); ?></h3>
	</header>
	<div class="tribe-options">
		<?php
		foreach ( $options as $option ) {

			$option_slug = md5( sanitize_title( $option ) );
			$field_slug  = $field['slug'];
			$option_id   = "tribe-tickets-meta_{$field_slug}" . ( $attendee_id ? '_' . $attendee_id : '' ) . "_{$option_slug}";
			$slug        = $field_slug . '_' . $option_slug;
			?>
			<label for="<?php echo esc_attr( $option_id ); ?>" class="tribe-tickets-meta-field-header">
				<input
					type="checkbox"
					id="<?php echo esc_attr( $option_id ); ?>"
					class="ticket-meta"
					name="tribe-tickets-meta[<?php echo $attendee_id ?>][<?php echo esc_attr( $slug ); ?>]"
					value="<?php echo esc_attr( $option ); ?>"
					<?php checked( true, in_array( $slug, $value ) ); ?>
					<?php disabled( $is_restricted ); ?>
				>
				<span class="tribe-tickets-meta-option-label">
					<?php echo wp_kses_post( $option ); ?>
				</span>
			</label>
			<?php
		}
		?>
	</div>
</div>
