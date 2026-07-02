<?php
$post_id = get_the_ID();

/** @var Tribe__Tickets__Tickets_Handler $handler */
$handler = tribe( 'tickets.handler' );

$total_tickets = tribe_get_event_capacity( $post_id, true );

// only show if there are tickets
if ( empty( $total_tickets ) ) {
	return;
}

$label = sprintf(
	/* translators: %s: singular ticket label, e.g. "Ticket" */
	_x( 'Total %s Capacity:', 'ticket capacity label in admin panel', 'event-tickets' ),
	tribe_get_ticket_label_singular( 'total_capacity_label' )
);
$title = sprintf(
	/* translators: %s: lowercase singular ticket label, e.g. "ticket" */
	_x( 'The total number of possible %s attendees for this event', 'ticket capacity label description in admin panel', 'event-tickets' ),
	tribe_get_ticket_label_singular_lowercase( 'total_capacity_label_description' )
);
?>
<span id="ticket_form_total_capacity">
	<?php echo esc_html( $label ); ?>
	<span id="ticket_form_total_capacity_value" title="<?php echo esc_attr( $title ); ?>">
		<?php
		switch ( $total_tickets ) {
			case -1:
				printf( '<a href="#" id="capacity_form_toggle">%s</a>', esc_html( $handler->unlimited_term ) );
				break;
			case 0:
				printf( '<a href="#" id="capacity_form_toggle">%s</a>', esc_html__( 'No tickets created yet', 'event-tickets' ) );
				break;
			default:
				printf( '<a href="#" id="capacity_form_toggle">%s</a>', esc_html( number_format_i18n( $total_tickets ) ) );
				break;
		}
		?>
	</span>
</span>
