<?php
/**
 * Block: Tickets
 * Extra column, available
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/tickets/item/extra/available.php
 *
 * See more documentation about our views templating system.
 *
 * @link    http://m.tri.be/1amp
 *
 * @since   TBD
 *
 * @version TBD
 *
 * @var Tribe__Tickets__Editor__Template $this
 * @var Tribe__Tickets__Ticket_Object    $ticket
 * @var bool                             $is_mini True if it's in the mini cart context.
 */

// Bail if it is in the mini cart context.
if ( ! empty( $is_mini ) ) {
	return;
}

?>
<div class="tribe-common-b3 tribe-tickets__item__extra__available">
	<?php $this->template( 'v2/tickets/item/extra/available/unlimited', [ 'ticket' => $ticket ] ); ?>

	<?php $this->template( 'v2/tickets/item/extra/available/quantity', [ 'ticket' => $ticket ] ); ?>
</div>
