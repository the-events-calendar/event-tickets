<?php
/**
 * Tickets List: Available Dates
 *
 * Displays the available dates of tickets below the ticket title.
 *
 * @since TBD Shows the time of each sales date, tells an upcoming or expired ticket in its event's timezone, and prints
 *            the attributes the Relative Sale Dates classic script reads.
 *
 * @var \Tribe__Template              $this                           [Global] Template object.
 * @var Tribe__Tickets__Ticket_Object $ticket                         [Global] The tickets provider instance.
 * @var array<string,string>          $relative_sale_dates_attributes The attributes of a ticket with a relative sales window.
 */

defined( 'ABSPATH' ) || die();

if ( empty( $ticket->start_date ) ) {
	return;
}
$classes = [
	'tribe-tickets__tickets-editor-ticket-available-dates',
	'dashicons-before',
];
// The same fallbacks as the Relative Sale Dates scripts, which rewrite these dates when a ticket follows the event.
$date_format = ( tribe_get_date_format( true ) ?: 'F j, Y' ) . ' ' . ( tribe_get_time_format() ?: 'g:i a' );
$start_time  = strtotime( $ticket->start_date . ' ' . $ticket->start_time );
// Formatted in UTC, where `strtotime()` read it: `date_i18n()` would move a time in the site's DST gap.
$utc         = new DateTimeZone( 'UTC' );
$date_string = wp_date( $date_format, $start_time, $utc ) . ' - ';

// Icon design is based on if start date is in the future or not, in the event timezone the dates are stored in.
$classes[] = $ticket->date_is_earlier()
	? 'dashicons-arrow-right-alt'
	: 'dashicons-clock';

if ( ! empty( $ticket->end_date ) ) {
	$end_time     = strtotime( $ticket->end_date . ' ' . $ticket->end_time );
	$date_string .= wp_date( $date_format, $end_time, $utc );

	// Add a class to differentiate tickets that are no longer on sale.
	if ( $ticket->date_is_later() ) {
		$classes[] = 'tribe-tickets__tickets-editor-ticket-available-dates-icon-expired';
	}
}

?>
<div <?php tec_classes( $classes ); ?><?php tribe_attributes( $relative_sale_dates_attributes ?? [] ); ?>>
	<?php echo esc_html( $date_string ); ?>
</div>
