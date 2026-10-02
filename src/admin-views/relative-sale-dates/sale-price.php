<?php
/**
 * The sale price fields of the classic ticket form, for a Tickets Commerce ticket on an event.
 *
 * Replaces `commerce/metabox/sale-price`: the checkbox, the sale price and the two date inputs are that template's,
 * unchanged, the dates shown for a specific date. The mode and relative inputs have no `name`: the rule is sent as JSON
 * in the `ticket_sale_price_relative` field.
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var string                                                                                     $sale_price             The sale price.
 * @var bool                                                                                       $sale_checkbox_on       Whether the ticket has a sale price.
 * @var string                                                                                     $sale_start_date        The sale price start date.
 * @var string                                                                                     $sale_end_date          The sale price end date.
 * @var array<string,string>                                                                       $start_date_errors      The errors for the sale price start date.
 * @var array<string,string>                                                                       $end_date_errors        The errors for the sale price end date.
 * @var array<string,string>                                                                       $sale_price_errors      The errors for the sale price.
 * @var bool                                                                                       $is_free_ticket_allowed Whether free tickets are allowed.
 * @var array{start: array{mode: string, value: int, unit: int}, end: array{mode: string, value: int, unit: int}} $sale_price_window The mode and relative values of each end of the sale price window.
 * @var string                                                                                     $sale_price_rule_json   The stored sale price rule as JSON, or an empty string when there is none.
 */

use TEC\Tickets\Relative_Sale_Dates\Rule;
use TEC\Tickets\Relative_Sale_Dates\Sale_Price_Boundary;
use TEC\Tickets\Relative_Sale_Dates\Sale_Price_Rule;
use TEC\Tickets\Relative_Sale_Dates\Sale_Price_Save;

$sale_price_validation_attrs = [
	'data-validation-is-less-than="#ticket_price"',
	'data-validation-error="' . esc_attr( wp_json_encode( $sale_price_errors ) ) . '"',
];

if ( ! $is_free_ticket_allowed ) {
	$sale_price_validation_attrs[] = 'data-validation-is-greater-than="0"';
}

$modes = [
	'start' => [
		Sale_Price_Rule::MODE_NOW => _x( 'Now', 'When the sale price starts.', 'event-tickets' ),
		Rule::MODE_RELATIVE       => _x( 'On a relative date', 'When the sale price starts.', 'event-tickets' ),
		Rule::MODE_SPECIFIC       => _x( 'On a specific date', 'When the sale price starts.', 'event-tickets' ),
	],
	'end'   => [
		Rule::MODE_RELATIVE => _x( 'On a relative date', 'When the sale price ends.', 'event-tickets' ),
		Rule::MODE_SPECIFIC => _x( 'On a specific date', 'When the sale price ends.', 'event-tickets' ),
	],
];

// The msgids match the sales window template's and the script's, so one translation serves all of them.
$get_units = static fn( int $value ): array => [
	DAY_IN_SECONDS  => _n( 'day', 'days', $value, 'event-tickets' ),
	WEEK_IN_SECONDS => _n( 'week', 'weeks', $value, 'event-tickets' ),
];

$labels = [
	'start' => [
		'mode'  => __( 'Sale Starts:', 'event-tickets' ),
		'value' => __( 'Number of units before the event that the sale price starts', 'event-tickets' ),
		'unit'  => __( 'Unit of the sale price start', 'event-tickets' ),
		'date'  => __( 'Sale price start date', 'event-tickets' ),
	],
	'end'   => [
		'mode'  => __( 'Sale Ends:', 'event-tickets' ),
		'value' => __( 'Number of units before the event that the sale price ends', 'event-tickets' ),
		'unit'  => __( 'Unit of the sale price end', 'event-tickets' ),
		'date'  => __( 'Sale price end date', 'event-tickets' ),
	],
];

?>
<div class="ticket_sale_price_wrapper ticket_form_right">
	<div>
		<input
			type="checkbox"
			name="ticket_add_sale_price"
			id="ticket_add_sale_price"
			<?php checked( $sale_checkbox_on ); ?>
		>
		<label
			for="ticket_add_sale_price"
			class="ticket_form_label"
		>
			<?php esc_html_e( 'Add Sale Price', 'event-tickets' ); ?>
		</label>
	</div>
	<div class="ticket_sale_price tribe-dependent"
		data-depends="#ticket_add_sale_price"
		data-condition-is-checked
	>
		<div class="ticket_sale_price-field">
			<label for="ticket_sale_price" class="ticket_form_label">
				<?php esc_html_e( 'Sale Price:', 'event-tickets' ); ?>
			</label>
			<input
				type="text"
				id="ticket_sale_price"
				name="ticket_sale_price"
				class="ticket_field"
				size="7"
				value="<?php echo esc_attr( $sale_price ); ?>"
				<?php echo implode( ' ', $sale_price_validation_attrs ); // phpcs:ignore ?>
			/>
		</div>
		<?php foreach ( [ 'start', 'end' ] as $sale_end ) : ?>
			<?php $fields = $sale_price_window[ $sale_end ]; ?>
		<div class="ticket_sale_price-field tec-tickets-relative-sale-dates">
			<label class="ticket_form_label" for="ticket_sale_<?php echo esc_attr( $sale_end ); ?>_mode">
				<?php echo esc_html( $labels[ $sale_end ]['mode'] ); ?>
			</label>
			<select
				class="tribe-dependency"
				id="ticket_sale_<?php echo esc_attr( $sale_end ); ?>_mode"
			>
				<?php foreach ( $modes[ $sale_end ] as $sale_mode => $mode_label ) : ?>
					<option value="<?php echo esc_attr( $sale_mode ); ?>" <?php selected( $fields['mode'], $sale_mode ); ?>><?php echo esc_html( $mode_label ); ?></option>
				<?php endforeach; ?>
			</select>
			<div
				class="tribe-dependent tec-tickets-relative-sale-dates__relative"
				data-depends="#ticket_sale_<?php echo esc_attr( $sale_end ); ?>_mode"
				data-condition="<?php echo esc_attr( Rule::MODE_RELATIVE ); ?>"
			>
				<input
					type="number"
					min="<?php echo esc_attr( Sale_Price_Boundary::MIN_VALUE ); ?>"
					max="<?php echo esc_attr( Sale_Price_Boundary::MAX_VALUE ); ?>"
					step="1"
					id="ticket_sale_<?php echo esc_attr( $sale_end ); ?>_value"
					value="<?php echo esc_attr( $fields['value'] ); ?>"
					aria-label="<?php echo esc_attr( $labels[ $sale_end ]['value'] ); ?>"
				/>
				<select
					id="ticket_sale_<?php echo esc_attr( $sale_end ); ?>_unit"
					aria-label="<?php echo esc_attr( $labels[ $sale_end ]['unit'] ); ?>"
				>
					<?php foreach ( $get_units( $fields['value'] ) as $unit => $unit_label ) : ?>
						<option value="<?php echo esc_attr( $unit ); ?>" <?php selected( $fields['unit'], $unit ); ?>><?php echo esc_html( $unit_label ); ?></option>
					<?php endforeach; ?>
				</select>
				<span><?php echo esc_html_x( 'before the event starts', 'What a relative ticket sale date is measured from.', 'event-tickets' ); ?></span>
			</div>
			<div
				class="tribe-dependent tec-tickets-relative-sale-dates__specific"
				data-depends="#ticket_sale_<?php echo esc_attr( $sale_end ); ?>_mode"
				data-condition="<?php echo esc_attr( Rule::MODE_SPECIFIC ); ?>"
			>
				<label class="screen-reader-text" for="ticket_sale_<?php echo esc_attr( $sale_end ); ?>_date">
					<?php echo esc_html( $labels[ $sale_end ]['date'] ); ?>
				</label>
				<?php if ( 'start' === $sale_end ) : ?>
				<input
					autocomplete="off"
					type="text"
					class="tribe-datepicker tribe-field-ticket_sale_start_date ticket_field"
					name="ticket_sale_start_date"
					id="ticket_sale_start_date"
					size="10"
					value="<?php echo esc_attr( $sale_start_date ); ?>"
					data-validation-type="datepicker"
					data-validation-is-less-or-equal-to="#ticket_sale_end_date"
					data-validation-error="<?php echo esc_attr( wp_json_encode( $start_date_errors ) ); ?>"
				/>
				<?php else : ?>
				<input
					autocomplete="off"
					type="text"
					class="tribe-datepicker tribe-field-ticket_sale_end_date ticket_field"
					name="ticket_sale_end_date"
					id="ticket_sale_end_date"
					size="10"
					value="<?php echo esc_attr( $sale_end_date ); ?>"
					data-validation-type="datepicker"
					data-validation-is-greater-or-equal-to="#ticket_sale_start_date"
					data-validation-error="<?php echo esc_attr( wp_json_encode( $end_date_errors ) ); ?>"
				/>
				<?php endif; ?>
			</div>
			<?php if ( 'end' === $sale_end ) : ?>
			<span
				class="tec-tickets-relative-sale-dates__helper"
				id="ticket_sale_price_length"
				aria-live="polite"
			></span>
			<?php endif; ?>
		</div>
		<?php endforeach; ?>
		<p class="tec-tickets-relative-sale-dates__error ticket_sale_price-field" id="ticket_sale_price_error" role="alert"></p>
		<input
			type="hidden"
			name="<?php echo esc_attr( Sale_Price_Save::DATA_KEY ); ?>"
			id="ticket_sale_price_relative"
			value="<?php echo esc_attr( $sale_price_rule_json ); ?>"
		/>
	</div>
</div>
