<?php
/**
 * Renders the sale price fields for TicketsCommerce.
 *
 * @since TBD
 *
 * @var Ticket_Object $ticket The ticket object.
 * @var string        $sale_price The sale price.
 * @var bool          $sale_checkbox_on The sale price.
 */

use Tribe__Tickets__Ticket_Object as Ticket_Object;

?>
<div class="ticket_sale_price_wrapper ticket_form_right">
	<!--Checkbox to toggle sale price field-->
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
		<?php esc_html_e( 'Add Sale Price:', 'event-tickets' ); ?>
	</label>
	<div class="ticket_sale_price tribe-dependent"
		data-depends="#ticket_add_sale_price"
		data-condition-is-checked
	>
		<label for="ticket_sale_price" class="ticket_form_label"><?php esc_html_e( 'Sale Price:', 'event-tickets' ); ?></label>
		<input
			type="text"
			id="ticket_sale_price"
			name="ticket_sale_price"
			class="ticket_field"
			size="7"
			value="<?php echo esc_attr( $sale_price ); ?>"
			data-validation-is-greater-than="0"
			data-validation-is-less-than="#ticket_price"
			data-validation-error-message="<?php esc_attr_e( 'Sale price must be less than the regular price.', 'event-tickets' ); ?>"
		/>
	</div>
</div>
