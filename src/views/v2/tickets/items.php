<?php
/**
 * Block: Tickets
 * Items
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/tickets/items.php
 *
 * See more documentation about our views templating system.
 *
 * @link    https://m.tri.be/1amp Help article for RSVP & Ticket template files.
 *
 * @since   TBD
 * @version TBD
 *
 * @var Tribe__Tickets__Editor__Template   $this            Template instance.
 * @var Tribe__Tickets__Ticket_Object[]    $tickets         List of tickets.
 * @var Tribe__Tickets__Ticket_Object[]    $tickets_on_sale List of tickets on sale.
 * @var Tribe__Tickets__Commerce__Currency $currency        The Currency Object.
 */

if ( empty( $tickets_on_sale ) ) {
	return;
}

/** @var Tribe__Tickets__Tickets_Handler $tickets_handler */
$tickets_handler = tribe( 'tickets.handler' );

foreach ( $tickets_on_sale as $key => $ticket ) {
	$has_shared_cap = $tickets_handler->has_shared_capacity( $ticket );

	$this->template(
		'v2/tickets/item',
		[
			'ticket'              => $ticket,
			'key'                 => $key,
			'data_available'      => 0 === $tickets_handler->get_ticket_max_purchase( $ticket->ID ) ? 'false' : 'true',
			'has_shared_cap'      => $has_shared_cap,
			'data_has_shared_cap' => $has_shared_cap ? 'true' : 'false',
			'currency_symbol'     => $currency->get_currency_symbol( $ticket->ID, true ),
		]
	);
}

