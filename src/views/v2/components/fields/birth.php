<?php
/**
 * This template renders the Birth field.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/components/fields/birth.php
 *
 * @TODO: Update this file once this one is merged https://github.com/moderntribe/event-tickets/pull/1677
 *
 * @since   TBD
 *
 * @version TBD
 *
 * @see     Tribe__Tickets_Plus__Meta__Field__Birth
 */

$field_name  = tribe_tickets_ar_field_name( $ticket->ID, $field->slug );
$field_id    = tribe_tickets_ar_field_id( $ticket->ID, $field->slug );
$required    = tribe_tickets_ar_field_is_required( $field );
$birth_field = $field;
$field       = (array) $field;
$disabled    = false;
$classes     = [
	'tribe-common-b1',
	'tribe-tickets__form-field',
	'tribe-tickets__form-field--required' => $required,
];
?>
<div class="tribe_horizontal_datepicker__container">
	<div <?php tribe_classes( $classes ); ?>>
		<label
			class="tribe-common-b2--min-medium tribe-tickets__form-field-label"
			for="<?php echo esc_attr( $field_id ); ?>"
		><?php echo wp_kses_post( $field['label'] ); ?><?php tribe_required_label( $required ); ?></label>

		<!-- Month -->
		<div class="tribe_horizontal_datepicker">
			<select
				<?php tribe_disabled( $disabled ); ?>
				<?php tribe_required( $required ); ?>
				class="tribe_horizontal_datepicker__month"
			>
				<option value="" disabled selected><?php esc_html_e( 'Month', 'tribe-event-plus' ); ?></option>
				<?php foreach ( $birth_field->get_months() as $month_number => $month_name ) : ?>
					<option value="<?php echo esc_attr( $month_number ); ?>"><?php echo esc_html( $month_name ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<!-- Day -->
		<div class="tribe_horizontal_datepicker">
			<select
				<?php tribe_disabled( $disabled ); ?>
				<?php tribe_required( $required ); ?>
				class="tribe_horizontal_datepicker__day"
			>
				<option value="" disabled selected><?php esc_html_e( 'Day', 'tribe-event-plus' ); ?></option>
				<?php foreach ( $birth_field->get_days() as $birth_day ) : ?>
					<option value="<?php echo esc_attr( $birth_day ); ?>"><?php echo esc_html( $birth_day ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<!-- Year -->
		<div class="tribe_horizontal_datepicker">
			<select
				<?php tribe_disabled( $disabled ); ?>
				<?php tribe_required( $required ); ?>
				class="tribe_horizontal_datepicker__year"
			>
				<option value="" disabled selected><?php esc_html_e( 'Year', 'tribe-event-plus' ); ?></option>
				<?php foreach ( $birth_field->get_years() as $birth_year ) : ?>
					<option value="<?php echo esc_attr( $birth_year ); ?>"><?php echo esc_html( $birth_year ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
	</div>
	<div>
		<input
			type="hidden"
			class="tribe-tickets__form-field-input tribe_horizontal_datepicker__value"
			name="<?php echo esc_attr( $field_name ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			<?php tribe_disabled( $disabled ); ?>
			<?php tribe_required( $required ); ?>
		/>
	</div>
</div>
