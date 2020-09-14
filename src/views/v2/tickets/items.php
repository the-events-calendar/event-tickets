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
 * @link http://m.tri.be/1amp
 *
 * @since TBD
 * @version TBD
 *
 * @var Tribe__Tickets__Ticket_Object[] $tickets         List of tickets.
 * @var Tribe__Tickets__Ticket_Object[] $tickets_on_sale List of tickets on sale.
 */

if ( empty( $tickets_on_sale ) ) {
	return;
}

foreach ( $tickets_on_sale as $key => $ticket ) :

	$this->template( 'v2/tickets/item', [ 'ticket' => $ticket, 'key' => $key ] );

endforeach;

