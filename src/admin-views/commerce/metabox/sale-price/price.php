<?php
/**
 * The sale price input of a Tickets Commerce ticket.
 *
 * Shared by `commerce/metabox/sale-price` and `relative-sale-dates/sale-price`.
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var string               $sale_price             The sale price.
 * @var array<string,string> $sale_price_errors      The errors for the sale price.
 * @var bool                 $is_free_ticket_allowed Whether free tickets are allowed.
 */

$sale_price_validation_attrs = [
	'data-validation-is-less-than="#ticket_price"',
	'data-validation-error="' . esc_attr( wp_json_encode( $sale_price_errors ) ) . '"',
];

// Do not allow free ticket in sale price if not allowed.
if ( ! $is_free_ticket_allowed ) {
	$sale_price_validation_attrs[] = 'data-validation-is-greater-than="0"';
}

?>
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
				<?php echo implode( ' ', $sale_price_validation_attrs ); // phpcs:ignore StellarWP.XSS.EscapeOutput.OutputNotEscaped -- Each attribute is escaped where it is built above. ?>
			/>
		</div>
