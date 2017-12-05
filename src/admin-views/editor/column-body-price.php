<?php
$price = null;
if ( method_exists( $provider_obj, 'get_price_value' ) ) {
	$price = $provider_obj->get_price_value( $ticket->ID );
}

// Add price column to body
?>
<td class="ticket_price" data-label="<?php esc_html_e( 'Price:', 'event-tickets-plus' ); ?>">
	<?php
	if ( $price ) {
		// outputs HTML - can't escape
		echo $provider_obj->get_price_html( $ticket->ID );
	} else {
		if ( 'Tribe__Tickets__RSVP' === $ticket->provider_class ) {
			esc_html_e( 'RSVP', 'event-tickets-plus' );
		} else {
			esc_html_e( 'Free', 'event-tickets-plus' );
		}
	}
	?>
</td>
