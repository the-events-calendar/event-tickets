<?php
/**
 * The start and end dates field for the ticket editor.
 *
 * @since 5.8.0
 * @since 5.18.0 Removed start and end date tooltips.
 * @since TBD Moved the date and time inputs to `editor/panel/fields/date-inputs`.
 *
 * @version TBD
 *
 * @var string $ticket_start_date The start date of the ticket.
 * @var string $ticket_end_date The end date of the ticket.
 * @var string $ticket_start_time The start time of the ticket.
 * @var string $ticket_end_time The end time of the ticket.
 * @var string $ticket_start_date_aria_label The aria label for the start date.
 * @var string $ticket_end_date_aria_label The aria label for the end date.
 * @var array  $start_date_errors The errors for the start date.
 * @var array  $end_date_errors The errors for the end date.
 * @var string $timepicker_step The timepicker step.
 * @var string $timepicker_round The timepicker round.
 * @var array<string,mixed> $ticket The ticket.
 */

defined( 'ABSPATH' ) || exit;

?>
<div class="input_block">
	<label class="ticket_form_label ticket_form_left" for="ticket_start_date">
		<?php esc_html_e( 'Start sale:', 'event-tickets' ); ?>
	</label>
	<div class="ticket_form_right">
<?php $this->template( 'editor/panel/fields/date-inputs', [ 'sales_end' => 'start' ] ); ?>
	</div>
</div>
<div class="input_block">
	<label class="ticket_form_label ticket_form_left" for="ticket_end_date">
		<?php esc_html_e( 'End sale:', 'event-tickets' ); ?>
	</label>
	<div class="ticket_form_right">
<?php $this->template( 'editor/panel/fields/date-inputs', [ 'sales_end' => 'end' ] ); ?>
	</div>
</div>
