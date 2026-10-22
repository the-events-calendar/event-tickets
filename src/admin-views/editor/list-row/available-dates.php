<?php
/**
 * Tickets List: Available Dates
 *
 * Displays the available dates of tickets below the ticket title.
 *
 * @since TBD Shows the time of each sales date, and prints the attributes the Relative Sale Dates classic script reads.
 *
 * @var \Tribe__Template              $this                           [Global] Template object.
 * @var Tribe__Tickets__Ticket_Object $ticket                         [Global] The tickets provider instance.
 * @var array<string,string>          $relative_sale_dates_attributes The attributes of a ticket with a relative sales window.
 */

defined( 'ABSPATH' ) || die();

if ( empty( $ticket->start_date ) ) {
	return;
}
$classes     = [
	'tribe-tickets__tickets-editor-ticket-available-dates',
	'dashicons-before',
];
$date_format = tribe_get_date_format( true ) . ' ' . tribe_get_time_format();
$start_time  = strtotime( $ticket->start_date . ' ' . $ticket->start_time );
$date_string = date_i18n( $date_format, $start_time ) . ' - ';

// Icon design is based on if start date is in the future or not.
$classes[] = ( current_time( 'timestamp' ) < $start_time ) // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
	? 'dashicons-arrow-right-alt'
	: 'dashicons-clock';

if ( ! empty( $ticket->end_date ) ) {
	$end_time     = strtotime( $ticket->end_date . ' ' . $ticket->end_time );
	$date_string .= date_i18n( $date_format, $end_time );

	// Add a class to differentiate tickets that are no longer on sale.
	if ( current_time( 'timestamp' ) > $end_time ) { // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
		$classes[] = 'tribe-tickets__tickets-editor-ticket-available-dates-icon-expired';
	}
}

?>
<div <?php tec_classes( $classes ); ?><?php tribe_attributes( $relative_sale_dates_attributes ?? [] ); ?>>
	<?php echo esc_html( $date_string ); ?>
</div>
