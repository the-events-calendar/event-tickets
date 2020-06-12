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

// Bail if RSVP isn't in stock.
if ( ! $rsvp->is_in_stock() ) {
	return;
}

use Tribe__Date_Utils as Dates;
$days_to_rsvp = Dates::date_diff( $rsvp->start_date, $rsvp->end_date );

echo wp_kses_post(
	sprintf(
		// Translators: 1: opening span. 2: the number of remaining days to RSVP. 3: Closing span. 4: The RSVP label.
		_x(
			'%1$s %2$s %3$s days left to %4$s',
			'Days to RSVP',
			'event-tickets'
		),
		'<span class="tribe-tickets__rsvp-availability-days-left tribe-common-b2--bold">',
		$days_to_rsvp,
		'</span>',
		tribe_get_rsvp_label_singular( 'Days to RSVP' )
	)
);
