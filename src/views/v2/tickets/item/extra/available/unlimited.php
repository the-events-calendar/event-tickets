<?php
/**
 * Block: Tickets
 * Extra column, available Unlimited
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/tickets/extra/item/available/unlimited.php
 *
 * See more documentation about our views templating system.
 *
 * @link http://m.tri.be/1amp
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var Tribe__Tickets__Editor__Template $this
 * @var Tribe__Tickets__Ticket_Object    $ticket
 */

/**
 * Allows hiding of "unlimited" to be toggled on/off conditionally.
 *
 * @since 4.11.1
 *
 * @param int $show_unlimited allow showing of "unlimited".
 */
$show_unlimited = apply_filters( 'tribe_tickets_block_show_unlimited_availability', true, $ticket->available() );
$is_unlimited   = $ticket->available() === - 1;

if (
	! $is_unlimited
	|| ! $show_unlimited
) {
	return;
}

esc_html_e( 'Unlimited', 'event-tickets' );
