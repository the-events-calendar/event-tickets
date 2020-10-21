<?php
/**
 * Block: Tickets
 * Extra column, available Quantity
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/tickets/item/extra/available/quantity.php
 *
 * See more documentation about our views templating system.
 *
 * @link    http://m.tri.be/1amp
 *
 * @since   TBD
 *
 * @version TBD
 *
 * @var Tribe__Tickets__Editor__Template $this The Template Object.
 * @var Tribe__Tickets__Ticket_Object $ticket  The Ticket Object.
 * @var int $threshold                         The threshold value to show or hide quantity available.
 */

 if ( 0 !== $threshold && $threshold < $ticket->available() ) {
	return;
}

echo wp_kses_post(
	sprintf(
	// Translators: 1: opening span. 2: the number of remaining tickets to buy. 3: Closing span.
		_x(
			'%1$s %2$s %3$s available',
			'Tickets available',
			'event-tickets'
		),
		'<span class="tribe-tickets__tickets-item-extra-available-quantity">',
		$ticket->available(),
		'</span>'
	)
);
