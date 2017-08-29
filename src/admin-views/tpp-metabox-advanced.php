<?php
include dirname( __FILE__ ) . '/price-fields.php';
?>

<?php if ( $this->supports_global_stock() ) : ?>
	<tr class="<?php $this->tr_class(); ?> global-stock-mode">
		<td><label for="ticket_tpp_global_stock"><?php esc_html_e( 'Global stock mode:', 'event-tickets' ); ?></label></td>
		<td>
			<?php echo $this->global_stock_mode_selector( $global_stock_mode ); ?>
		</td>
	</tr>

	<tr class="<?php $this->tr_class(); ?> global-stock-mode sales-cap-field">
		<td><label for="ticket_tpp_global_stock_cap"><?php esc_html_e( 'Cap sales:', 'event-tickets' ); ?></label></td>
		<td>
			<input type='text' id='ticket_tpp_global_stock_cap' name='ticket_tpp_global_stock_cap' class="ticket_field" size='7'
				value='<?php echo esc_attr( $global_stock_cap ); ?>'/>
			<p class="description"><?php esc_html_e( '(This is the maximum allowed number of sales for this ticket.)', 'event-tickets' ); ?></p>
		</td>
	</tr>
<?php endif; ?>

<tr class="<?php $this->tr_class(); ?> stock">
	<td><label for="ticket_tpp_stock"><?php esc_html_e( 'Stock:', 'event-tickets' ); ?></label></td>
	<td>
		<input type='text' id='ticket_tpp_stock' name='ticket_tpp_stock' class="ticket_field" size='7'
			value='<?php echo esc_attr( $stock ); ?>'/>
		<p class="description"><?php esc_html_e( "(Total available # of this ticket type. Once they're gone, ticket type is sold out.)", 'event-tickets' ); ?></p>
	</td>
</tr>

<?php
if ( apply_filters( 'tribe_tickets_default_purchase_limit', 0 ) ) {
	?>
	<tr class="<?php $this->tr_class(); ?>">
		<td><label for="ticket_purchase_limit"><?php esc_html_e( 'Purchase limit:', 'event-tickets' ); ?></label></td>
		<td>
			<input type='text' id='ticket_purchase_limit' name='ticket_purchase_limit' class="ticket_field" size='7' data-default-value='<?php echo esc_attr( $purchase_limit ); ?>'/>
			<p class="description"><?php esc_html_e( 'The maximum number of tickets per order. (0 means there\'s no limit)', 'event-tickets' ); ?></p>
		</td>
	</tr>
	<?php
}

if ( apply_filters( 'tribe_events_tickets_tpp_display_sku', true ) ) {
	?>
	<tr class="<?php $this->tr_class(); ?>">
		<td><label for="ticket_tpp_sku"><?php esc_html_e( 'SKU:', 'event-tickets' ); ?></label></td>
		<td>
			<input type='text' id='ticket_tpp_sku' name='ticket_tpp_sku' class="ticket_field" size='7' value='<?php echo esc_attr( $sku ); ?>'/>
			<p class="description"><?php esc_html_e( "(A unique identifying code for each ticket type you're selling)", 'event-tickets' ); ?></p>
		</td>
	</tr>
	<?php
}