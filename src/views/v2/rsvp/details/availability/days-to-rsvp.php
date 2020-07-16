<?php
/**
 * Block: RSVP
 * Details Availability - Days to RSVP
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp/details/availability/days-to-rsvp.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link  {INSERT_ARTICLE_LINK_HERE}
 *
 * @var Tribe__Tickets__Ticket_Object $rsvp The rsvp ticket object.
 *
 * @since TBD
 *
 * @version TBD
 */

use Tribe__Date_Utils as Dates;

$days_to_rsvp = Dates::date_diff( current_time( 'mysql' ), $rsvp->end_date );
$days_to_rsvp = floor( $days_to_rsvp );

// Only show this if it is happening within the next week.
if ( 6 < $days_to_rsvp ){
	return;
}

if ( 0 < $days_to_rsvp ) {
	echo wp_kses_post(
		sprintf(
			// Translators: 1: opening span. 2: the number of remaining days to RSVP. 3: Closing span. 4: The RSVP label.
			_nx(
				'%1$s %2$s %3$s day left to %4$s',
				'%1$s %2$s %3$s days left to %4$s',
				$days_to_rsvp,
				'Days to RSVP',
				'event-tickets'
			),
			'<span class="tribe-tickets__rsvp-availability-days-left tribe-common-b2--bold">',
			number_format_i18n( $days_to_rsvp ),
			'</span>',
			tribe_get_rsvp_label_singular( 'Days to RSVP' )
		)
	);
} else {
	echo wp_kses_post(
		sprintf(
			// Translators: %s: The RSVP label.
			_x(
				'Last day to %s',
				'Last day to RSVP',
				'event-tickets'
			),
			tribe_get_rsvp_label_singular( 'Last day to RSVP' )
		)
	);
}
