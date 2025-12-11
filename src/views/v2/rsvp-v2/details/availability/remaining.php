<?php
/**
 * RSVP V2: Remaining Spots
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp-v2/details/availability/remaining.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var Tribe__Tickets__Ticket_Object $ticket       The RSVP ticket object.
 * @var int                           $post_id      The event post ID.
 * @var int|false                     $days_to_rsvp Days until RSVP closes, or false.
 * @var int                           $threshold    The threshold for showing remaining count.
 */

// Ensure proper context.
if ( empty( $ticket ) || empty( $post_id ) ) {
	return;
}

$remaining_tickets = $ticket->remaining();
$threshold         = $this->get( 'threshold', 0 );

if ( 0 !== $threshold && $threshold < $remaining_tickets ) {
	return;
}

echo wp_kses_post(
	sprintf(
		// Translators: 1: opening span. 2: the number of remaining tickets to RSVP. 3: Closing span.
		_x(
			'%1$s %2$s %3$s remaining',
			'Remaining RSVP quantity',
			'event-tickets'
		),
		'<span class="tribe-tickets__rsvp-v2-availability-quantity tribe-common-b2--bold">',
		$remaining_tickets,
		'</span>'
	) . ( false !== $days_to_rsvp ? ',' : '' )
);
