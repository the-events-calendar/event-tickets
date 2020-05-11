<?php
/**
 * This template renders a Single Ticket content
 * composed by Title and Description currently
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/registration-js/attendees/fields/birth.php
 *
 * @since   TBD
 * @version TBD
 *
 * @see     Tribe__Tickets_Plus__Meta__Field__Birth
 */

$required    = isset( $field->required ) && 'on' === $field->required ? true : false;
$option_id   = "tribe-tickets-meta_{$field->slug}_{$ticket->ID}{{data.attendee_id}}";
$birth_field = $field;
$field       = (array) $field;
$field_name  = 'tribe-tickets-meta[' . $ticket->ID . '][{{data.attendee_id}}][' . esc_attr( $field['slug'] ) . ']';
$disabled    = false;
$classes     = [
		'tribe-common-b1',
		'tribe-field',
		'tribe-tickets__item__attendee__field__birth',
		'tribe-tickets-meta-required' => $required,
];
?>
<div class="tribe-horizontal-date-picker-container">
	<div <?php tribe_classes( $classes ); ?> >
		<label
				class="tribe-common-b2--min-medium tribe-tickets-meta-label"
				for="<?php echo esc_attr( $birth_field->month_id ); ?>"
		><?php echo wp_kses_post( $field['label'] ); ?><?php tribe_required_label( $required ); ?></label>

		<div class="tribe_day_month_year_datepicker tribe__tickets__item__attendee__field__birth__month">
			<select
					<?php tribe_disabled( $disabled ); ?>
					<?php tribe_required( $required ); ?>
					class="tribe-horizontal-date-picker-month"
					onchange="tribe_events_horizontal_date_picker_update_value(this)"
			>
				<option value="" disabled selected><?php esc_html_e( 'Month', 'tribe-event-plus' ); ?></option>
				<?php foreach ( $birth_field->get_months() as $month_number => $month_name ): ?>
					<option value="<?php esc_attr( $month_number ); ?>"><?php echo esc_attr( $month_name ) ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="tribe_day_month_year_datepicker tribe__tickets__item__attendee__field__birth__day">
			<select
					<?php tribe_disabled( $disabled ); ?>
					<?php tribe_required( $required ); ?>
					class="tribe-horizontal-date-picker-day"
					onchange="tribe_events_horizontal_date_picker_update_value(this)"
			>
				<option value="" disabled selected><?php esc_html_e( 'Day', 'tribe-event-plus' ); ?></option>
				<?php foreach ( $birth_field->get_days() as $birth_day ): ?>
					<option><?php echo esc_attr( $birth_day ) ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="tribe_day_month_year_datepicker tribe__tickets__item__attendee__field__birth__year">
			<select
					<?php tribe_disabled( $disabled ); ?>
					<?php tribe_required( $required ); ?>
					class="tribe-horizontal-date-picker-year"
					onchange="tribe_events_horizontal_date_picker_update_value(this)"
			>
				<option value="" disabled selected><?php esc_html_e( 'Year', 'tribe-event-plus' ); ?></option>
				<?php foreach ( $birth_field->get_years() as $birth_year ): ?>
					<option><?php echo esc_attr( $birth_year ) ?></option>
				<?php endforeach; ?>
			</select>
		</div>
	</div>
	<div>
		<input
				type="hidden"
				class="tribe-common-form-control-birth__input ticket-meta tribe-horizontal-date-picker-value"
				name="<?php echo esc_attr( $field_name ); ?>"
				value="<?php echo esc_attr( $value ); ?>"
				<?php tribe_disabled( $disabled ); ?>
				<?php tribe_required( $required ); ?>
		/>
	</div>
</div>