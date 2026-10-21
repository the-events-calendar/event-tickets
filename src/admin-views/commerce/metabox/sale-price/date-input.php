<?php
/**
 * The date input of one end of a Tickets Commerce ticket's sale price.
 *
 * Shared by `commerce/metabox/sale-price` and `relative-sale-dates/sale-price`.
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var string               $sale_end          The end of the sale price, `start` or `end`.
 * @var string               $sale_start_date   The sale start date.
 * @var string               $sale_end_date     The sale end date.
 * @var array<string,string> $start_date_errors The errors for the sale start date.
 * @var array<string,string> $end_date_errors   The errors for the sale end date.
 */

defined( 'ABSPATH' ) || exit;

$is_start = 'start' === $sale_end;
$other    = $is_start ? 'end' : 'start';

?>
			<input
				autocomplete="off"
				type="text"
				class="tribe-datepicker tribe-field-ticket_sale_<?php echo esc_attr( $sale_end ); ?>_date ticket_field"
				name="ticket_sale_<?php echo esc_attr( $sale_end ); ?>_date"
				id="ticket_sale_<?php echo esc_attr( $sale_end ); ?>_date"
				size="10"
				value="<?php echo esc_attr( $is_start ? $sale_start_date : $sale_end_date ); ?>"
				data-validation-type="datepicker"
				data-validation-is-<?php echo esc_attr( $is_start ? 'less' : 'greater' ); ?>-or-equal-to="#ticket_sale_<?php echo esc_attr( $other ); ?>_date"
				data-validation-error="<?php echo esc_attr( wp_json_encode( $is_start ? $start_date_errors : $end_date_errors ) ); ?>"
			/>
