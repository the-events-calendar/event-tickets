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
 * @link    https://m.tri.be/1amp Help article for RSVP & Ticket template files.
 *
 * @since   TBD
 *
 * @version TBD
 *
 * @var Tribe__Tickets__Editor__Template $this    The Template Object.
 * @var Tribe__Tickets__Ticket_Object    $ticket  The Ticket Object.
 * @var bool                             $is_mini True if it's in the mini cart context.
 */

// Bail if it is in the mini cart context.
if ( ! empty( $is_mini ) ) {
	return;
}

$available_count = $ticket->available();

/**
 * Allows hiding of "unlimited" to be toggled on/off conditionally.
 *
 * @since 4.11.1
 *
 * @var bool $show_unlimited  Whether to show the "unlimited" text.
 * @var int  $available_count The quantity of Available tickets based on the Attendees number.
 */
$show_unlimited = apply_filters( 'tribe_tickets_block_show_unlimited_availability', true, $available_count );

$context = [
	'ticket'          => $ticket,
	'show_unlimited'  => (bool) $show_unlimited,
	'available_count' => $available_count,
	'is_unlimited'    => - 1 === $available_count,
];
?>

<div class="tribe-common-b3 tribe-tickets__tickets-item-extra-available">

	<?php $this->template( 'v2/tickets/item/extra/available/unlimited', $context ); ?>

	<?php $this->template( 'v2/tickets/item/extra/available/quantity', $context ); ?>

</div>
