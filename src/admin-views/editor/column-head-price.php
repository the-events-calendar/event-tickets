<?php
/**
 * @var string|null $ticket_type The type of ticket the table is for.
 */

if ( isset( $ticket_type ) && $ticket_type === 'rsvp' ) {
	// If the ticket is an RSVP, we don't want to show a label in the price column.
	$label = '';
} else {
	// For other types of tickets, we want to show the price label.
	$label = __( 'Price', 'event-tickets' );
}
?>

<th class="ticket_price"><?php echo esc_html( $label ); ?></th>
