<?php
if ( ! isset( $post_id ) ) {
	$post_id = get_the_ID();
}

if ( ! isset( $ticket_id ) ) {
	$provider = null;
	$ticket_id = null;
	$ticket = null;
} else {
	$provider = tribe_tickets_get_ticket_provider( $ticket_id );
	$ticket = $provider->get_ticket( $post_id, $ticket_id );

	if ( $ticket->on_sale ) {
		$sale_price = $ticket->price;
		$price = $ticket->regular_price;
	} else {
		$sale_price = null;
		$price = $ticket->price;
	}
}
?>
<div
	class="price tribe-dependent"
	data-depends="#Tribe__Tickets__RSVP_radio"
	data-condition-is-not-checked
>
	<div class="input_block">
		<label for="ticket_price" class="ticket_form_label ticket_form_left"><?php esc_html_e( 'Price:', 'event-tickets-plus' ); ?></label>
		<input
			type="text"
			id="ticket_price"
			name="ticket_price"
			class="ticket_field ticket_form_right"
			size="7"
			value="<?php echo esc_attr( $ticket ? $price : null ); ?>"
		/>
		<p class="description ticket_form_right"><?php esc_html_e( 'Leave blank for free tickets', 'event-tickets-plus' ) ?></p>
	</div>

	<?php if ( $ticket && $ticket->on_sale ) : ?>
	<div class="input_block">
		<label for="ticket_sale_price" class="ticket_form_label ticket_form_left"><?php esc_html_e( 'Sale Price:', 'event-tickets-plus' ) ?></label>
		<input
			type="text"
			id="ticket_sale_price"
			name='ticket_sale_price'
			class="ticket_field ticket_form_right"
			size="7"
			value="<?php echo esc_attr( $ticket ? $sale_price : null ); ?>"
			readonly
		/>
		<p class="description ticket_form_right"><?php esc_html_e( 'Current sale price - this can be managed via the product editor', 'event-tickets-plus' ) ?></p>
	</div>
	<?php endif; ?>
</div>
