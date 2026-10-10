<?php
/**
 * The sale price fields of the classic ticket form, for a Tickets Commerce ticket on an event.
 *
 * Replaces `commerce/metabox/sale-price`: the checkbox, the sale price and the two date inputs are that template's,
 * unchanged, the dates shown for a specific date. Each end of the window is rendered by
 * `relative-sale-dates/window-boundary`; the rule is sent as JSON in the `ticket_sale_price_relative` field.
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
 * @var Window_Kind                                                                                $window_kind            The sale price window kind.
 * @var array{start: array{mode: string, value: int, unit: int}, end: array{mode: string, value: int, unit: int}} $window_fields The mode and relative values of each end of the sale price window.
 * @var string                                                                                     $rule_json              The stored sale price rule as JSON, or an empty string when there is none.
 */

use TEC\Tickets\Relative_Sale_Dates\Rule;
use TEC\Tickets\Relative_Sale_Dates\Window_Kind;

defined( 'ABSPATH' ) || die();

$sale_price_validation_attrs = [
	'data-validation-is-less-than="#ticket_price"',
	'data-validation-error="' . esc_attr( wp_json_encode( $sale_price_errors ) ) . '"',
];

if ( ! $is_free_ticket_allowed ) {
	$sale_price_validation_attrs[] = 'data-validation-is-greater-than="0"';
}

$modes = [
	'start' => [
		Rule::MODE_NOW      => _x( 'Now', 'When the sale price starts.', 'event-tickets' ),
		Rule::MODE_RELATIVE => _x( 'On a relative date', 'When the sale price starts.', 'event-tickets' ),
		Rule::MODE_SPECIFIC => _x( 'On a specific date', 'When the sale price starts.', 'event-tickets' ),
	],
	'end'   => [
		Rule::MODE_RELATIVE => _x( 'On a relative date', 'When the sale price ends.', 'event-tickets' ),
		Rule::MODE_SPECIFIC => _x( 'On a specific date', 'When the sale price ends.', 'event-tickets' ),
	],
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

$render_date_inputs = static function ( string $sale_end ) use ( $sale_start_date, $sale_end_date, $start_date_errors, $end_date_errors ): void {
	if ( 'start' === $sale_end ) {
		?>
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
		<?php
	} else {
		?>
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
		<?php
	}
};

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
			<?php
			$boundary_args = [
				'window_kind'        => $window_kind,
				'boundary_end'       => $sale_end,
				'boundary_fields'    => $window_fields[ $sale_end ],
				'field_prefix'       => 'ticket_sale',
				'mode_labels'        => $modes[ $sale_end ],
				'boundary_labels'    => $labels[ $sale_end ],
				'date_input_id'      => "ticket_sale_{$sale_end}_date",
				'shows_helper_text'  => false,
				'render_date_inputs' => $render_date_inputs,
			];
			?>
		<div class="ticket_sale_price-field tec-tickets-relative-sale-dates">
			<label class="ticket_form_label" for="ticket_sale_<?php echo esc_attr( $sale_end ); ?>_mode">
				<?php echo esc_html( $labels[ $sale_end ]['mode'] ); ?>
			</label>
			<?php $this->template( 'relative-sale-dates/window-boundary', $boundary_args ); ?>
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
			name="<?php echo esc_attr( $window_kind->get_rule_keys()['data'] ); ?>"
			id="ticket_sale_price_relative"
			value="<?php echo esc_attr( $rule_json ); ?>"
		/>
	</div>
</div>
