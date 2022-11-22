<?php
$post_id = get_the_ID();

/** @var Tribe__Tickets__Tickets_Handler $handler */
$handler = tribe( 'tickets.handler' );

$total_tickets = tribe_get_event_capacity( $post_id );

// only show if there are tickets
if ( empty( $total_tickets ) ) {
	return;
}
?>
<span id="ticket_form_total_capacity">
	<?php esc_html_e( 'Total Event Capacity:', 'event-tickets' ); ?>
	<span id="ticket_form_total_capacity_value" title="<?php esc_attr_e( 'The total number of possible attendees for this event', 'event-tickets' ); ?>">
		<?php
		switch ( $total_tickets ) {
			case -1:
				printf( '<i>%s</i>', esc_html( $handler->unlimited_term ) );
				break;
			case 0:
				printf( '<i>%s</i>', esc_html__( 'No tickets created yet', 'event-tickets' ) );
				break;
			default:
				echo esc_html( number_format_i18n( $total_tickets ) );
				break;
		}
		?>
	</span>
</span>
