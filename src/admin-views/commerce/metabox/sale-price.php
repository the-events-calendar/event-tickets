<?php
/**
 * Renders the sale price fields for Tickets Commerce.
 *
 * @since 5.9.0
 * @since 5.10.0 Added the $is_free_ticket_allowed parameter and updated validation for the Sale Price input field.
 *
 * @var Ticket_Object $ticket The ticket object.
 * @var string        $sale_price The sale price.
 * @var bool          $sale_checkbox_on The sale price.
 * @var string        $sale_start_date The sale start date.
 * @var string        $sale_end_date The sale end date.
 * @var array         $start_date_errors The start date errors.
 * @var array         $end_date_errors The end date errors.
 * @var array         $sale_price_errors The sale price errors.
 * @var bool          $is_free_ticket_allowed Whether free tickets are allowed.
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
<div class="ticket_sale_price_wrapper ticket_form_right">
	<!--Checkbox to toggle sale price fields-->
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
		<div class="ticket_sale_price-field">
			<label class="ticket_form_label" for="ticket_start_date">
				<?php esc_html_e( 'On sale from:', 'event-tickets' ); ?>
			</label>
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
			<span> <?php esc_html_e( 'to', 'event-tickets' ); ?> </span>
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
		</div>
	</div>
</div>
