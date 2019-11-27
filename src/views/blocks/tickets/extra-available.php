<?php
/**
 * Block: Tickets
 * Extra column, available
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/tickets/extra-available.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since 4.9.3
 * @since TBD Corrected amount of available/remaining tickets.
 *
 * @version TBD
 */

/** @var Tribe__Tickets__Ticket_Object $ticket */
$ticket = $this->get( 'ticket' );

if ( empty( $ticket->ID ) ) {
	return;
}

/** @var Tribe__Tickets__Tickets_Handler $tickets_handler */
$tickets_handler = tribe( 'tickets.handler' );

$available = $tickets_handler->get_ticket_max_purchase( $ticket->ID );

if ( -1 === $available ) {
	return;
}
?>
<div
	class="tribe-common-b3 tribe-tickets__item__extra__available"
>
	<?php $this->template( 'blocks/tickets/extra-available-quantity', [ 'ticket' => $ticket ] ); ?>
</div>