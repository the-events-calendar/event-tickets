<?php
/**
 * The sales window fields of the classic ticket form, for a Tickets Commerce ticket on an event.
 *
 * Replaces `editor/panel/fields/dates`: the date and time inputs are that template's, `editor/panel/fields/date-inputs`,
 * shown for a specific date. Each end of the window is rendered by `relative-sale-dates/window-boundary`; the rule is
 * sent as JSON in the `relative_sale_dates` field.
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var string                                                                                   $ticket_start_date            The start date of the ticket.
 * @var string                                                                                   $ticket_end_date              The end date of the ticket.
 * @var string                                                                                   $ticket_start_time            The start time of the ticket.
 * @var string                                                                                   $ticket_end_time              The end time of the ticket.
 * @var string                                                                                   $ticket_start_date_aria_label The aria label for the start date.
 * @var string                                                                                   $ticket_end_date_aria_label   The aria label for the end date.
 * @var array<string,string>                                                                     $start_date_errors            The errors for the start date.
 * @var string                                                                                   $timepicker_step              The timepicker step.
 * @var string                                                                                   $timepicker_round             The timepicker round.
 * @var Tribe__Tickets__Ticket_Object|null                                                       $ticket                       The ticket, or `null` for a new ticket.
 * @var Window_Kind                                                                              $window_kind                  The sales window kind.
 * @var array{start: array{mode: string, value: int, unit: int, anchor: string}, end: array{mode: string, value: int, unit: int, anchor: string}} $window_fields The mode and relative values of each end of the sales window.
 * @var string                                                                                   $rule_json                    The stored rule as JSON, or an empty string when there is none.
 */

use TEC\Tickets\Relative_Sale_Dates\Rule;
use TEC\Tickets\Relative_Sale_Dates\Window_Kind;

defined( 'ABSPATH' ) || die();

$modes = [
	'start' => [
		Rule::MODE_DEFAULT  => _x( 'Now', 'When ticket sales start.', 'event-tickets' ),
		Rule::MODE_RELATIVE => _x( 'On a relative date', 'When ticket sales start.', 'event-tickets' ),
		Rule::MODE_SPECIFIC => _x( 'On a specific date', 'When ticket sales start.', 'event-tickets' ),
	],
	'end'   => [
		Rule::MODE_DEFAULT  => _x( 'When the event starts', 'When ticket sales end.', 'event-tickets' ),
		Rule::MODE_RELATIVE => _x( 'On a relative date', 'When ticket sales end.', 'event-tickets' ),
		Rule::MODE_SPECIFIC => _x( 'On a specific date', 'When ticket sales end.', 'event-tickets' ),
	],
];

$labels = [
	'start' => [
		'mode'     => __( 'Start Sales:', 'event-tickets' ),
		'relative' => _x( 'Starts', 'Opens a relative ticket sales start, as in "Starts 2 weeks before the event starts".', 'event-tickets' ),
		'value'    => __( 'Number of units before the event that sales start', 'event-tickets' ),
		'unit'     => __( 'Unit of the sales start', 'event-tickets' ),
		'anchor'   => __( 'What the sales start is measured from', 'event-tickets' ),
		'date'     => $ticket_start_date_aria_label,
	],
	'end'   => [
		'mode'     => __( 'End Sales:', 'event-tickets' ),
		'relative' => _x( 'Ends', 'Opens a relative ticket sales end, as in "Ends 1 hour before the event starts".', 'event-tickets' ),
		'value'    => __( 'Number of units before the event that sales end', 'event-tickets' ),
		'unit'     => __( 'Unit of the sales end', 'event-tickets' ),
		'anchor'   => __( 'What the sales end is measured from', 'event-tickets' ),
		'date'     => $ticket_end_date_aria_label,
	],
];

$render_date_inputs = function ( string $sales_end ): void {
	$this->template( 'editor/panel/fields/date-inputs', [ 'sales_end' => $sales_end ] );
};

?>
<?php foreach ( [ 'start', 'end' ] as $sales_end ) : ?>
	<?php
	$boundary_args = [
		'window_kind'        => $window_kind,
		'boundary_end'       => $sales_end,
		'boundary_fields'    => $window_fields[ $sales_end ],
		'field_prefix'       => 'ticket_sales',
		'mode_labels'        => $modes[ $sales_end ],
		'boundary_labels'    => $labels[ $sales_end ],
		'date_input_id'      => "ticket_{$sales_end}_date",
		'shows_helper_text'  => true,
		'render_date_inputs' => $render_date_inputs,
	];
	?>
<div class="input_block tec-tickets-relative-sale-dates">
	<label class="ticket_form_label ticket_form_left" for="ticket_sales_<?php echo esc_attr( $sales_end ); ?>_mode">
		<?php echo esc_html( $labels[ $sales_end ]['mode'] ); ?>
	</label>
	<div class="ticket_form_right">
		<?php $this->template( 'relative-sale-dates/window-boundary', $boundary_args ); ?>
	</div>
</div>
<?php endforeach; ?>
<p class="tec-tickets-relative-sale-dates__error tec-tickets-relative-sale-dates__error--sales-window ticket_form_right" id="ticket_sales_window_error" role="alert"></p>
<input
	type="hidden"
	name="<?php echo esc_attr( $window_kind->get_rule_keys()['data'] ); ?>"
	id="ticket_relative_sale_dates"
	value="<?php echo esc_attr( $rule_json ); ?>"
/>
