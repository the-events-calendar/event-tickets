<?php
/**
 * Block: Tickets
 * Quantity > Mini (for mini cart)
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/tickets/item/quantity-mini.php
 *
 * See more documentation about our views templating system.
 *
 * @link http://m.tri.be/1amp
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var Tribe__Tickets__Ticket_Object $ticket  The ticket object.
 * @var bool                          $is_mini If the template is in "mini cart" context.
 */

// Bail if it's not in "mini cart" context.
if ( empty( $is_mini ) ) {
	return;
}

?>
<div class="tribe-ticket-quantity">0</div>