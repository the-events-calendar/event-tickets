<?php
/**
 * The sales window fields of the classic ticket form, for a Tickets Commerce ticket on an event.
 *
 * Replaces `editor/panel/fields/dates`: the date and time inputs are that template's, `editor/panel/fields/date-inputs`,
 * shown for a specific date. Each end of the window is rendered by `relative-sale-dates/window-boundary`; the rule is
 * sent as JSON in the `relative_sale_dates` field.
 *
 * A ticket preset renders the `preset` variant: a preset has no event, so it offers no specific date and no helper
 * text, and its form sends the rule in a field of its own. Its script judges the window itself, with the message the
 * error carries.
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
 * @var string                                                                                   $variant                      The form the window is rendered in, `ticket` or `preset`.
 * @var string                                                                                   $field_prefix                 The prefix of the ids of the window's inputs.
 * @var array{start: string[], end: string[]}                                                    $window_modes                 The modes each end of the window offers.
 * @var array<int,string[]>                                                                      $unit_names                   For a form with no Event Tickets script, the name of each unit for every number, keyed by the unit.
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
		'mode'     => __( 'Start sale:', 'event-tickets' ),
		'relative' => _x( 'Starts', 'Opens a relative ticket sales start, as in "Starts 2 weeks before the event starts".', 'event-tickets' ),
		'value'    => __( 'Number of units before the event that sales start', 'event-tickets' ),
		'unit'     => __( 'Unit of the sales start', 'event-tickets' ),
		'anchor'   => __( 'What the sales start is measured from', 'event-tickets' ),
		'date'     => $ticket_start_date_aria_label ?? '',
	],
	'end'   => [
		'mode'     => __( 'End sale:', 'event-tickets' ),
		'relative' => _x( 'Ends', 'Opens a relative ticket sales end, as in "Ends 1 hour before the event starts".', 'event-tickets' ),
		'value'    => __( 'Number of units before the event that sales end', 'event-tickets' ),
		'unit'     => __( 'Unit of the sales end', 'event-tickets' ),
		'anchor'   => __( 'What the sales end is measured from', 'event-tickets' ),
		'date'     => $ticket_end_date_aria_label ?? '',
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
		'boundary_modes'     => $window_modes[ $sales_end ],
		'boundary_fields'    => $window_fields[ $sales_end ],
		'field_prefix'       => $field_prefix,
		'mode_labels'        => $modes[ $sales_end ],
		'boundary_labels'    => $labels[ $sales_end ],
		'date_input_id'      => "ticket_{$sales_end}_date",
		'shows_helper_text'  => 'ticket' === $variant,
		'render_date_inputs' => $render_date_inputs,
	];
	?>
<div class="input_block tec-tickets-relative-sale-dates">
	<label class="ticket_form_label ticket_form_left" for="<?php echo esc_attr( "{$field_prefix}_{$sales_end}_mode" ); ?>">
		<?php echo esc_html( $labels[ $sales_end ]['mode'] ); ?>
	</label>
	<div class="ticket_form_right">
		<?php $this->template( 'relative-sale-dates/window-boundary', $boundary_args ); ?>
	</div>
</div>
<?php endforeach; ?>
<?php if ( 'preset' === $variant ) : ?>
<p class="tec-tickets-relative-sale-dates__error tec-tickets-relative-sale-dates__error--sales-window ticket_form_right" id="<?php echo esc_attr( "{$field_prefix}_window_error" ); ?>" role="alert" data-ends-before-start="<?php echo esc_attr( $window_kind->get_error( 'endsBeforeStart' )->get_error_message() ); ?>"></p>
<div class="tec-tickets-relative-sale-dates__unit-names" hidden data-unit-names="<?php echo esc_attr( wp_json_encode( $unit_names ) ); ?>"></div>
<?php else : ?>
<p class="tec-tickets-relative-sale-dates__error tec-tickets-relative-sale-dates__error--sales-window ticket_form_right" id="<?php echo esc_attr( "{$field_prefix}_window_error" ); ?>" role="alert"></p>
<input
	type="hidden"
	name="<?php echo esc_attr( $window_kind->get_rule_keys()['data'] ); ?>"
	id="ticket_relative_sale_dates"
	value="<?php echo esc_attr( $rule_json ); ?>"
/>
<?php endif; ?>
