<?php
/**
 * The date and time inputs of one end of the ticket sales window.
 *
 * Shared by `editor/panel/fields/dates` and `relative-sale-dates/sales-window`.
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var string                             $sales_end                    The end of the window, `start` or `end`.
 * @var Tribe__Tickets__Ticket_Object|null $ticket                       The ticket, or `null` for a new ticket.
 * @var string                             $ticket_start_date            The start date of the ticket.
 * @var string                             $ticket_end_date              The end date of the ticket.
 * @var string                             $ticket_start_time            The start time of the ticket.
 * @var string                             $ticket_end_time              The end time of the ticket.
 * @var string                             $ticket_start_date_aria_label The aria label for the start date.
 * @var string                             $ticket_end_date_aria_label   The aria label for the end date.
 * @var array<string,string>               $start_date_errors            The errors for the start date.
 * @var string                             $timepicker_step              The timepicker step.
 * @var string                             $timepicker_round             The timepicker round.
 */

use Tribe__Date_Utils as Date_Utils;

defined( 'ABSPATH' ) || exit;

$is_start = 'start' === $sales_end;

if ( $ticket ) {
	$date = $is_start ? $ticket_start_date : $ticket_end_date;
	$time = $is_start ? $ticket_start_time : $ticket_end_time;
} else {
	// A new ticket goes on sale today and stops tomorrow, at midnight.
	$datepicker_format = Date_Utils::datepicker_formats( Date_Utils::get_datepicker_format_index() );
	$date              = Date_Utils::build_date_object( $is_start ? 'now' : '+1 day' )->format( $datepicker_format );
	$time              = '00:00:00';
}

?>
		<input
			autocomplete="off"
			type="text"
			class="tribe-datepicker tribe-field-<?php echo esc_attr( $sales_end ); ?>_date ticket_field"
			name="ticket_<?php echo esc_attr( $sales_end ); ?>_date"
			id="ticket_<?php echo esc_attr( $sales_end ); ?>_date"
			value="<?php echo esc_attr( $date ); ?>"
<?php if ( $is_start ) : ?>
			data-validation-type="datepicker"
			data-validation-is-less-or-equal-to="#ticket_end_date"
			data-validation-error="<?php echo esc_attr( wp_json_encode( $start_date_errors ) ); ?>"
<?php endif; ?>
		/>
		<span class="helper-text hide-if-js"><?php esc_html_e( 'YYYY-MM-DD', 'event-tickets' ); ?></span>
		<span class="datetime_seperator"> <?php esc_html_e( 'at', 'event-tickets' ); ?> </span>
		<input
			autocomplete="off"
			type="text"
			class="tribe-timepicker tribe-field-<?php echo esc_attr( $sales_end ); ?>_time ticket_field"
			name="ticket_<?php echo esc_attr( $sales_end ); ?>_time"
			id="ticket_<?php echo esc_attr( $sales_end ); ?>_time"
			<?php echo Tribe__View_Helpers::is_24hr_format() ? 'data-format="H:i"' : ''; ?>
			data-step="<?php echo esc_attr( $timepicker_step ); ?>"
			data-round="<?php echo esc_attr( $timepicker_round ); ?>"
			value="<?php echo esc_attr( $time ); ?>"
			aria-label="<?php echo esc_attr( $is_start ? $ticket_start_date_aria_label : $ticket_end_date_aria_label ); ?>"
		/>
		<span class="helper-text hide-if-js"><?php esc_html_e( 'HH:MM', 'event-tickets' ); ?></span>
