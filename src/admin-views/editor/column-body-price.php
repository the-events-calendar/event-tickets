<?php
$price = null;
if ( method_exists( $provider_obj, 'get_price_value' ) ) {
	$price = $provider_obj->get_price_value( $ticket->ID );
}

// Add price column to body
?>
<td class="ticket_price" data-label="<?php esc_html_e( 'Price:', 'event-tickets' ); ?>">
	<?php
	if ( $price ) {
		echo esc_html( $price->get_currency() );
	} else {
		if ( 'Tribe__Tickets__RSVP' === $ticket->provider_class ) {
			echo esc_html( tribe_get_rsvp_label_singular( basename( __FILE__ ) ) );
		} else {
			esc_html_e( 'Free', 'event-tickets' );
		}
	}
	?>
</td>
