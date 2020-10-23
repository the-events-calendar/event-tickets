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
 * @link    https://m.tri.be/1amp Help article for RSVP & Ticket template files.
 *
 * @since   TBD
 *
 * @version TBD
 *
 * @var Tribe__Tickets__Editor__Template $this            The template instance.
 * @var Tribe__Tickets__Ticket_Object    $ticket          The ticket object.
 * @var int                              $threshold       The threshold value to show or hide quantity available.
 * @var int                              $available_count The quantity of Available tickets based on the Attendees number.
 * @var bool                             $show_unlimited  Whether to allow showing of "unlimited".
 * @var bool                             $is_unlimited    Whether the ticket has unlimited quantity.
 */
if (
	0 !== $threshold
	&& $threshold < $available_count
) {
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
		$available_count,
		'</span>'
	)
);
