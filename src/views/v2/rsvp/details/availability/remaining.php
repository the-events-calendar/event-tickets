<?php
/**
 * Block: RSVP
 * Details Availability - Remaining
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp/details/availability/remaining.php
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

/** @var Tribe__Settings_Manager $settings_manager */
$settings_manager = tribe( 'settings.manager' );

$threshold         = $settings_manager::get_option( 'ticket-display-tickets-left-threshold', 0 );
$remaining_tickets = $rsvp->remaining();

/**
 * Overwrites the threshold to display "# tickets left".
 *
 * @param int   $threshold Stock threshold to trigger display of "# tickets left"
 * @param array $data      Ticket data.
 * @param int   $event_id  Event ID.
 *
 * @since 4.11.1
 */
$threshold = absint( apply_filters( 'tribe_display_rsvp_block_tickets_left_threshold', $threshold, tribe_events_get_ticket_event( $rsvp ) ) );

if ( 0 !== $threshold && $threshold < $remaining_tickets ) {
	return;
}

echo wp_kses_post(
	sprintf(
		// Translators: 1: opening span. 2: the number of remaining tickets to RSVP. 3: Closing span.
		_x(
			'%1$s %2$s %3$s remaining, ',
			'Remaining RSVP quantity',
			'event-tickets'
		),
		'<span class="tribe-tickets__rsvp-availability-quantity tribe-common-b2--bold">',
		$remaining_tickets,
		'</span>'
	)
);
