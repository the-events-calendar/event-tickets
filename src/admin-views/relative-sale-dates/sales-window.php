<?php
/**
 * The sales window fields of the classic ticket form, for a Tickets Commerce ticket on an event.
 *
 * Replaces `editor/panel/fields/dates`: the date and time inputs are that template's, unchanged, shown for a specific
 * date. The mode and relative inputs have no `name`: the rule is sent as JSON in the `relative_sale_dates` field.
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
 * @var array{start: array{mode: string, value: int, unit: int, anchor: string}, end: array{mode: string, value: int, unit: int, anchor: string}} $sales_window The mode and relative values of each end of the sales window.
 * @var string                                                                                   $rule_json                    The stored rule as JSON, or an empty string when there is none.
 */

use TEC\Tickets\Relative_Sale_Dates\Boundary;
use TEC\Tickets\Relative_Sale_Dates\Rule;
use TEC\Tickets\Relative_Sale_Dates\Ticket_Save;
use Tribe__Date_Utils as Date_Utils;

defined( 'ABSPATH' ) || die();

$datepicker_format = Tribe__Date_Utils::datepicker_formats( Tribe__Date_Utils::get_datepicker_format_index() );
//phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
$default_start_date = Date_Utils::build_date_object( 'now' )->format( $datepicker_format );
$default_start_time = '00:00:00';
//phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
$default_end_date = Date_Utils::build_date_object( '+1 day' )->format( $datepicker_format );

$default_end_time = '00:00:00';

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

$units = [
	MINUTE_IN_SECONDS => _x( 'minutes', 'Unit of a relative ticket sale date.', 'event-tickets' ),
	HOUR_IN_SECONDS   => _x( 'hours', 'Unit of a relative ticket sale date.', 'event-tickets' ),
	DAY_IN_SECONDS    => _x( 'days', 'Unit of a relative ticket sale date.', 'event-tickets' ),
	WEEK_IN_SECONDS   => _x( 'weeks', 'Unit of a relative ticket sale date.', 'event-tickets' ),
];

$anchors = [
	Rule::ANCHOR_START => _x( 'before the event starts', 'What a relative ticket sale date is measured from.', 'event-tickets' ),
	Rule::ANCHOR_END   => _x( 'before the event ends', 'What a relative ticket sale date is measured from.', 'event-tickets' ),
];

$labels = [
	'start' => [
		'mode'     => __( 'Start sale:', 'event-tickets' ),
		'relative' => _x( 'Starts', 'Opens a relative ticket sales start, as in "Starts 2 weeks before the event starts".', 'event-tickets' ),
		'value'    => __( 'Number of units before the event that sales start', 'event-tickets' ),
		'unit'     => __( 'Unit of the sales start', 'event-tickets' ),
		'anchor'   => __( 'What the sales start is measured from', 'event-tickets' ),
		'date'     => $ticket_start_date_aria_label,
	],
	'end'   => [
		'mode'     => __( 'End sale:', 'event-tickets' ),
		'relative' => _x( 'Ends', 'Opens a relative ticket sales end, as in "Ends 1 hour before the event starts".', 'event-tickets' ),
		'value'    => __( 'Number of units before the event that sales end', 'event-tickets' ),
		'unit'     => __( 'Unit of the sales end', 'event-tickets' ),
		'anchor'   => __( 'What the sales end is measured from', 'event-tickets' ),
		'date'     => $ticket_end_date_aria_label,
	],
];

?>
<?php foreach ( [ 'start', 'end' ] as $sales_end ) : ?>
	<?php $fields = $sales_window[ $sales_end ]; ?>
<div class="input_block tec-tickets-relative-sale-dates">
	<label class="ticket_form_label ticket_form_left" for="ticket_sales_<?php echo esc_attr( $sales_end ); ?>_mode">
		<?php echo esc_html( $labels[ $sales_end ]['mode'] ); ?>
	</label>
	<div class="ticket_form_right">
		<select
			class="tribe-dependency"
			id="ticket_sales_<?php echo esc_attr( $sales_end ); ?>_mode"
		>
			<?php foreach ( $modes[ $sales_end ] as $sales_mode => $mode_label ) : ?>
				<option value="<?php echo esc_attr( $sales_mode ); ?>" <?php selected( $fields['mode'], $sales_mode ); ?>><?php echo esc_html( $mode_label ); ?></option>
			<?php endforeach; ?>
		</select>
		<div
			class="tribe-dependent tec-tickets-relative-sale-dates__relative"
			data-depends="#ticket_sales_<?php echo esc_attr( $sales_end ); ?>_mode"
			data-condition="<?php echo esc_attr( Rule::MODE_RELATIVE ); ?>"
		>
			<span><?php echo esc_html( $labels[ $sales_end ]['relative'] ); ?></span>
			<input
				type="number"
				min="<?php echo esc_attr( Boundary::MIN_VALUE ); ?>"
				max="<?php echo esc_attr( Boundary::MAX_VALUE ); ?>"
				step="1"
				id="ticket_sales_<?php echo esc_attr( $sales_end ); ?>_value"
				value="<?php echo esc_attr( $fields['value'] ); ?>"
				aria-label="<?php echo esc_attr( $labels[ $sales_end ]['value'] ); ?>"
				aria-describedby="ticket_sales_<?php echo esc_attr( $sales_end ); ?>_helper"
			/>
			<select
				id="ticket_sales_<?php echo esc_attr( $sales_end ); ?>_unit"
				aria-label="<?php echo esc_attr( $labels[ $sales_end ]['unit'] ); ?>"
				aria-describedby="ticket_sales_<?php echo esc_attr( $sales_end ); ?>_helper"
			>
				<?php foreach ( $units as $unit => $unit_label ) : ?>
					<option value="<?php echo esc_attr( $unit ); ?>" <?php selected( $fields['unit'], $unit ); ?>><?php echo esc_html( $unit_label ); ?></option>
				<?php endforeach; ?>
			</select>
			<select
				id="ticket_sales_<?php echo esc_attr( $sales_end ); ?>_anchor"
				aria-label="<?php echo esc_attr( $labels[ $sales_end ]['anchor'] ); ?>"
				aria-describedby="ticket_sales_<?php echo esc_attr( $sales_end ); ?>_helper"
			>
				<?php foreach ( $anchors as $anchor => $anchor_label ) : ?>
					<option value="<?php echo esc_attr( $anchor ); ?>" <?php selected( $fields['anchor'], $anchor ); ?>><?php echo esc_html( $anchor_label ); ?></option>
				<?php endforeach; ?>
			</select>
			<span
				class="tec-tickets-relative-sale-dates__helper"
				id="ticket_sales_<?php echo esc_attr( $sales_end ); ?>_helper"
				aria-live="polite"
			></span>
		</div>
		<div
			class="tribe-dependent tec-tickets-relative-sale-dates__specific"
			data-depends="#ticket_sales_<?php echo esc_attr( $sales_end ); ?>_mode"
			data-condition="<?php echo esc_attr( Rule::MODE_SPECIFIC ); ?>"
		>
			<label class="screen-reader-text" for="ticket_<?php echo esc_attr( $sales_end ); ?>_date">
				<?php echo esc_html( $labels[ $sales_end ]['date'] ); ?>
			</label>
			<?php if ( 'start' === $sales_end ) : ?>
			<input
				autocomplete="off"
				type="text"
				class="tribe-datepicker tribe-field-start_date ticket_field"
				name="ticket_start_date"
				id="ticket_start_date"
				value="<?php echo esc_attr( $ticket ? $ticket_start_date : $default_start_date ); ?>"
				data-validation-type="datepicker"
				data-validation-is-less-or-equal-to="#ticket_end_date"
				data-validation-error="<?php echo esc_attr( wp_json_encode( $start_date_errors ) ); ?>"
			/>
			<span class="helper-text hide-if-js"><?php esc_html_e( 'YYYY-MM-DD', 'event-tickets' ); ?></span>
			<span class="datetime_seperator"> <?php esc_html_e( 'at', 'event-tickets' ); ?> </span>
			<input
				autocomplete="off"
				type="text"
				class="tribe-timepicker tribe-field-start_time ticket_field"
				name="ticket_start_time"
				id="ticket_start_time"
				<?php echo Tribe__View_Helpers::is_24hr_format() ? 'data-format="H:i"' : ''; ?>
				data-step="<?php echo esc_attr( $timepicker_step ); ?>"
				data-round="<?php echo esc_attr( $timepicker_round ); ?>"
				value="<?php echo esc_attr( $ticket ? $ticket_start_time : $default_start_time ); ?>"
				aria-label="<?php echo esc_attr( $ticket_start_date_aria_label ); ?>"
			/>
			<span class="helper-text hide-if-js"><?php esc_html_e( 'HH:MM', 'event-tickets' ); ?></span>
			<?php else : ?>
			<input
				autocomplete="off"
				type="text"
				class="tribe-datepicker tribe-field-end_date ticket_field"
				name="ticket_end_date"
				id="ticket_end_date"
				value="<?php echo esc_attr( $ticket ? $ticket_end_date : $default_end_date ); ?>"
			/>
			<span class="helper-text hide-if-js"><?php esc_html_e( 'YYYY-MM-DD', 'event-tickets' ); ?></span>
			<span class="datetime_seperator"> <?php esc_html_e( 'at', 'event-tickets' ); ?> </span>
			<input
				autocomplete="off"
				type="text"
				class="tribe-timepicker tribe-field-end_time ticket_field"
				name="ticket_end_time"
				id="ticket_end_time"
				<?php echo Tribe__View_Helpers::is_24hr_format() ? 'data-format="H:i"' : ''; ?>
				data-step="<?php echo esc_attr( $timepicker_step ); ?>"
				data-round="<?php echo esc_attr( $timepicker_round ); ?>"
				value="<?php echo esc_attr( $ticket ? $ticket_end_time : $default_end_time ); ?>"
				aria-label="<?php echo esc_attr( $ticket_end_date_aria_label ); ?>"
			/>
			<span class="helper-text hide-if-js"><?php esc_html_e( 'HH:MM', 'event-tickets' ); ?></span>
			<?php endif; ?>
		</div>
	</div>
</div>
<?php endforeach; ?>
<p class="tec-tickets-relative-sale-dates__error ticket_form_right" id="ticket_sales_window_error" role="alert"></p>
<input
	type="hidden"
	name="<?php echo esc_attr( Ticket_Save::DATA_KEY ); ?>"
	id="ticket_relative_sale_dates"
	value="<?php echo esc_attr( $rule_json ); ?>"
/>
