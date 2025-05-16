<?php
/**
 * The start and end dates field for the RSVP editor.
 *
 * @since   TBD
 *
 * @version TBD
 *
 * @var string                        $ticket_start_date            The start date of the ticket.
 * @var string                        $ticket_end_date              The end date of the ticket.
 * @var string                        $ticket_start_time            The start time of the ticket.
 * @var string                        $ticket_end_time              The end time of the ticket.
 * @var string                        $ticket_start_date_aria_label The aria label for the start date.
 * @var string                        $ticket_end_date_aria_label   The aria label for the end date.
 * @var array                         $start_date_errors            The errors for the start date.
 * @var array                         $end_date_errors              The errors for the end date.
 * @var string                        $timepicker_step              The timepicker step.
 * @var string                        $timepicker_round             The timepicker round.
 * @var Tribe__Tickets__Ticket_Object $tc_rsvp                      The TC RSVP object.
 */

use Tribe__Date_Utils as Date_Utils;

// Default values for the Start date & time fields.
$datepicker_format = Tribe__Date_Utils::datepicker_formats( Tribe__Date_Utils::get_datepicker_format_index() );
//phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
$default_start_date = Date_Utils::build_date_object( 'now' )->format( $datepicker_format );
$default_start_time = '00:00:00';
//phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
$default_end_date = Date_Utils::build_date_object( '+1 day' )->format( $datepicker_format );

$default_end_time = '00:00:00';

?>
<div class="input_block">
	<label class="ticket_form_label ticket_form_left" for="ticket_start_date">
		<?php esc_html_e( 'Open RSVP:', 'event-tickets' ); ?>
	</label>
	<div class="ticket_form_right">
		<input
			autocomplete="off"
			type="text"
			class="tribe-datepicker tribe-field-start_date ticket_field"
			name="rsvp_start_date"
			id="rsvp_start_date"
			value="<?php echo esc_attr( $tc_rsvp ? $ticket_start_date : $default_start_date ); ?>"
			data-validation-type="datepicker"

			data-validation-error="<?php echo esc_attr( wp_json_encode( $start_date_errors ) ); ?>"
		/>
		<span class="helper-text hide-if-js"><?php esc_html_e( 'YYYY-MM-DD', 'event-tickets' ); ?></span>
		<span class="datetime_seperator"> <?php esc_html_e( 'at', 'event-tickets' ); ?> </span>
		<input
			autocomplete="off"
			type="text"
			class="tribe-timepicker tribe-field-start_time ticket_field"
			name="rsvp_start_time"
			id="rsvp_start_time"
			<?php echo Tribe__View_Helpers::is_24hr_format() ? 'data-format="H:i"' : ''; ?>
			data-step="<?php echo esc_attr( $timepicker_step ); ?>"
			data-round="<?php echo esc_attr( $timepicker_round ); ?>"
			value="<?php echo esc_attr( $tc_rsvp ? $ticket_start_time : $default_start_time ); ?>"
			aria-label="<?php echo esc_attr( $ticket_start_date_aria_label ); ?>"
		/>
		<span class="helper-text hide-if-js"><?php esc_html_e( 'HH:MM', 'event-tickets' ); ?></span>
	</div>
</div>
<div class="input_block">
	<label class="ticket_form_label ticket_form_left" for="ticket_end_date">
		<?php esc_html_e( 'Close RSVP:', 'event-tickets' ); ?>
	</label>
	<div class="ticket_form_right">
		<input
			autocomplete="off"
			type="text"
			class="tribe-datepicker tribe-field-end_date ticket_field"
			name="rsvp_end_date"
			id="rsvp_end_date"
			value="<?php echo esc_attr( $tc_rsvp ? $ticket_end_date : $default_end_date ); ?>"
		/>
		<span class="helper-text hide-if-js"><?php esc_html_e( 'YYYY-MM-DD', 'event-tickets' ); ?></span>
		<span class="datetime_seperator"> <?php esc_html_e( 'at', 'event-tickets' ); ?> </span>
		<input
			autocomplete="off"
			type="text"
			class="tribe-timepicker tribe-field-end_time ticket_field"
			name="rsvp_end_time"
			id="rsvp_end_time"
			<?php echo Tribe__View_Helpers::is_24hr_format() ? 'data-format="H:i"' : ''; ?>
			data-step="<?php echo esc_attr( $timepicker_step ); ?>"
			data-round="<?php echo esc_attr( $timepicker_round ); ?>"
			value="<?php echo esc_attr( $tc_rsvp ? $ticket_end_time : $default_end_time ); ?>"
			aria-label="<?php echo esc_attr( $ticket_end_date_aria_label ); ?>"
		/>
		<span class="helper-text hide-if-js"><?php esc_html_e( 'HH:MM', 'event-tickets' ); ?></span>
	</div>
</div>
