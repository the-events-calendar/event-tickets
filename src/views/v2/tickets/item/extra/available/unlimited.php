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
 * @link    http://m.tri.be/1amp
 *
 * @since   TBD
 *
 * @version TBD
 *
 * @var Tribe__Tickets__Editor__Template $this           The template instance.
 * @var Tribe__Tickets__Ticket_Object    $ticket         The ticket object.
 * @var int                              $threshold      The threshold value to show or hide quantity available.
 * @var bool                             $show_unlimited Whether to allow showing of "unlimited".
 * @var bool                             $is_unlimited   Whether the ticket has unlimited quantity.
 */

if (
	! $is_unlimited
	|| ! $show_unlimited
) {
	return;
}

esc_html_e( 'Unlimited', 'event-tickets' );
