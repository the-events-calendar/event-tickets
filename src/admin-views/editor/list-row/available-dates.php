<?php
/**
 * Tickets List: Available Dates
 *
 * Displays the available dates of tickets below the ticket title.
 *
 * @var \Tribe__Template              $this   [Global] Template object.
 * @var Tribe__Tickets__Ticket_Object $ticket [Global] The tickets provider instance.
 */

if ( empty( $ticket->start_date ) ) {
	return;
}
$classes     = [
	'tribe-tickets__tickets-editor-ticket-available-dates',
	'dashicons-before',
];
$date_format = tribe_get_date_format( true );
$start_time  = strtotime( $ticket->start_date );
$date_string = date_i18n( $date_format, $start_time ) . ' - ';

// Icon design is based on if start date is in the future or not.
$classes[] = ( current_time( 'timestamp' ) < $start_time ) // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
	? 'dashicons-arrow-right-alt'
	: 'dashicons-clock';

if ( ! empty( $ticket->end_date ) ) {
	$end_time     = strtotime( $ticket->end_date );
	$date_string .= date_i18n( $date_format, $end_time );

	// Add a class to differentiate tickets that are no longer on sale.
	if ( current_time( 'timestamp' ) > $end_time ) { // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
		$classes[] = 'tribe-tickets__tickets-editor-ticket-available-dates-icon-expired';
	}
}

?>
<div <?php tribe_classes( $classes ); ?>>
	<?php echo $date_string; ?>
</div>
