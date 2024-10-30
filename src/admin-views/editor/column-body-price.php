<?php
/**
 * @var Tribe__Tickets__Ticket_Object $ticket The ticket object the price column is being rendered for.
 * @var Tribe__Tickets__Tickets $provider_obj The ticket provider object for this ticket.
 */


if ( $provider_obj instanceof Tribe__Tickets__RSVP ) {
	// If the ticket is an RSVP, we don't need to render the price column.
	echo '<td></td>';

	return;
}

$price = null;
if ( ! empty( $provider_obj ) && method_exists( $provider_obj, 'get_price_value' ) ) {
	$price = $provider_obj->get_price_value( $ticket->ID );
}

// Add price column to body.
?>
<td class="ticket_price" data-label="<?php esc_attr_e( 'Price:', 'event-tickets' ); ?>">
	<?php
	if ( $price ) {
		echo wp_kses(
			$provider_obj->get_price_html( $ticket->ID ),
			[
				'span' => [
					'class' => [],
				],
				'ins'  => [
					'class' => [],
				],
				'del'  => [
					'class' => [],
				],
				'bdi'  => [],
			] 
		);
	} elseif ( 'Tribe__Tickets__RSVP' === $ticket->provider_class ) {
			echo esc_html( tribe_get_rsvp_label_singular( basename( __FILE__ ) ) );
	} else {
		esc_html_e( 'Free', 'event-tickets' );
	}
	?>
</td>
