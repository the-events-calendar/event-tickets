<?php
$post_id = get_the_ID();

/** @var Tribe__Tickets__Tickets_Handler $handler */
$handler = tribe( 'tickets.handler' );

$total_tickets = tribe_get_event_capacity( $post_id );

// only show if there are tickets
if ( empty( $total_tickets ) ) {
	return;
}

$post_labels          = get_post_type_labels( get_post_type_object( get_post_type( $post_id ) ) );
$uppercase_post_label = $post_labels->singular_name ?? 'Event';
$label                = sprintf(
	/* translators: %s: uppercase post type label */
	_x(
		'Total %s Capacity:',
		'event-tickets'
	),
	$uppercase_post_label
);
$title                = sprintf(
	/* translators: %s: lowercase post type label */
	__( 'The total number of possible attendees for this %s', 'event-tickets' ),
	strtolower( $uppercase_post_label )
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
