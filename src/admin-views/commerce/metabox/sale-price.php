<?php
/**
 * Renders the sale price fields for Tickets Commerce.
 *
 * @since 5.9.0
 * @since 5.10.0 Added the $is_free_ticket_allowed parameter and updated validation for the Sale Price input field.
 * @since TBD Moved the checkbox, the sale price and the date inputs to `commerce/metabox/sale-price/*`.
 *
 * @version TBD
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

defined( 'ABSPATH' ) || exit;

?>
<div class="ticket_sale_price_wrapper ticket_form_right">
	<!--Checkbox to toggle sale price fields-->
<?php $this->template( 'commerce/metabox/sale-price/toggle' ); ?>
	<div class="ticket_sale_price tribe-dependent"
		data-depends="#ticket_add_sale_price"
		data-condition-is-checked
	>
<?php $this->template( 'commerce/metabox/sale-price/price' ); ?>
		<div class="ticket_sale_price-field">
			<label class="ticket_form_label" for="ticket_start_date">
				<?php esc_html_e( 'On sale from:', 'event-tickets' ); ?>
			</label>
<?php $this->template( 'commerce/metabox/sale-price/date-input', [ 'sale_end' => 'start' ] ); ?>
			<span> <?php esc_html_e( 'to', 'event-tickets' ); ?> </span>
<?php $this->template( 'commerce/metabox/sale-price/date-input', [ 'sale_end' => 'end' ] ); ?>
		</div>
	</div>
</div>
