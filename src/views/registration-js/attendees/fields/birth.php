<?php
/**
 * This template renders a Single Ticket content
 * composed by Title and Description currently
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/registration-js/attendees/fields/birth.php
 *
 * @since  TBD
 * @version TBD
 *
 * @see     Tribe__Tickets_Plus__Meta__Field__Birth
 *
 */
$required   = isset( $field->required ) && 'on' === $field->required ? true : false;
$option_id  = "tribe-tickets-meta_{$field->slug}_{$ticket->ID}{{data.attendee_id}}";
$field      = (array) $field;
$field_name = 'tribe-tickets-meta[' . $ticket->ID . '][{{data.attendee_id}}][' . esc_attr( $field['slug'] ) . ']';
$disabled   = false;
$classes    = [ 'tribe-common-b1', 'tribe-field', 'tribe-tickets__item__attendee__field__birth' ];

if ( $required ) {
	$classes[] = 'tribe-tickets-meta-required';
}

$days   = Tribe__Tickets_Plus__Meta__Field__Birth::get_days();
$months = Tribe__Tickets_Plus__Meta__Field__Birth::get_months();
$years  = Tribe__Tickets_Plus__Meta__Field__Birth::get_years();
?>
<div <?php tribe_classes( $classes ); ?> >
	<label
			class="tribe-common-b2--min-medium tribe-tickets-meta-label"
			for="<?php echo esc_attr( $option_id ); ?>"
	><?php echo wp_kses_post( $field['label'] ); ?><?php tribe_required_label( $required ); ?></label>

	<div class="tribe__tickets__item__attendee__field__birth tribe__tickets__item__attendee__field__birth__month">
		<select
				<?php tribe_disabled( $disabled ); ?>
				<?php tribe_required( $required ); ?>
				id="<?php echo esc_attr( $field_object->month_id ); ?>"
				name="<?php echo esc_attr( $field_object->month_id ); ?>"
		>
			<option value="" disabled selected><?php esc_html_e( "Month", 'tribe-event-plus' ); ?></option>
			<?php
			foreach ( $months as $month ) {
				$month = esc_attr( $month );
				echo "<option>$month</option>";
			}
			?>
		</select>
	</div>
	<div class="tribe__tickets__item__attendee__field__birth tribe__tickets__item__attendee__field__birth__day">
		<select
				<?php tribe_disabled( $disabled ); ?>
				<?php tribe_required( $required ); ?>
				id="<?php echo esc_attr( $field_object->day_id ); ?>"
				name="<?php echo esc_attr( $field_object->day_id ); ?>"
		>
			<option value="" disabled selected><?php esc_html_e( "Day", 'tribe-event-plus' ); ?></option>
			<?php
			foreach ( $days as $day ) {
				$day = esc_attr( $day );
				echo "<option>$day</option>";
			}
			?>
		</select>
	</div>
	<div class="tribe__tickets__item__attendee__field__birth tribe__tickets__item__attendee__field__birth__year">
		<select
				<?php tribe_disabled( $disabled ); ?>
				<?php tribe_required( $required ); ?>
				id="<?php echo esc_attr( $field_object->year_id ); ?>"
				name="<?php echo esc_attr( $field_object->year_id ); ?>"
		>
			<option value="" disabled selected><?php esc_html_e( "Year", 'tribe-event-plus' ); ?></option>
			<?php
			foreach ( $years as $year ) {
				$year = esc_attr( $year );
				echo "<option>$year</option>";
			}
			?>
		</select>
	</div>
</div>
<div>
	<input
			type="text"
			id="<?php echo esc_attr( $field_object->real_value_id ); ?>"
			class="tribe-common-form-control-birth__input ticket-meta"
			name="<?php echo esc_attr( $field_name ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			<?php tribe_disabled( $disabled ); ?>
			<?php tribe_required( $required ); ?>
	/>
</div>
