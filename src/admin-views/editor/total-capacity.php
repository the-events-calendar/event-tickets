<?php
$post_id = get_the_ID();
$total_tickets  = tribe( 'tickets.handler' )->get_total_event_capacity( $post_id );

// only show if there are tickets
if ( empty( $total_tickets ) ) {
	return;
}
?>
<span id="ticket_form_total_capacity">
	<?php esc_html_e( 'Total Event Capacity:', 'event-tickets-plus' ); ?>
	<span id="ticket_form_total_capacity_value" title="<?php esc_attr_e( 'The total number of possible attendees for this event', 'event-tickets-plus' ); ?>">
		<?php
		switch ( $total_tickets ) {
			case -1:
				?><i><?php echo esc_html( tribe( 'tickets.handler' )->unlimited_term ); ?></i><?php
				break;
			case 0:
				?><i><?php esc_html_e( 'No tickets created yet', 'event-tickets-plus' ); ?></i><?php
				break;
			default:
				echo absint( $total_tickets );
				break;
		}
		?>
	</span>
</span>