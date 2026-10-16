<?php
/**
 * The checkbox that adds a sale price to a Tickets Commerce ticket.
 *
 * Shared by `commerce/metabox/sale-price` and `relative-sale-dates/sale-price`.
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var bool $sale_checkbox_on Whether the ticket has a sale price.
 */

defined( 'ABSPATH' ) || exit;

?>
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
