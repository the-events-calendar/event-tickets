<?php
/**
 * Block: Tickets
 * Quantity Number
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/tickets/quantity-number.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link    https://m.tri.be/1amp Help article for RSVP & Ticket template files.
 *
 * @since   4.9
 * @since   4.10.8 Tweaked logic for unlimited maximum quantity allowed.
 * @since   4.11.5 The input's "max" is now always set.
 * @since   5.0.3 Removed duplicative vars.
 *
 * @version 5.0.3
 *
 * @var Tribe__Tickets__Ticket_Object    $ticket
 * @var Tribe__Tickets__Editor__Template $this
 */

$ticket = $this->get( 'ticket' );

if ( empty( $ticket->ID ) ) {
	return;
}
/** @var Tribe__Tickets__Tickets_Handler $handler */
$handler = tribe( 'tickets.handler' );

$max_at_a_time = $handler->get_ticket_max_purchase( $ticket->ID );

$classes = [ 'tribe-tickets__item__quantity__number' ];

if ( $must_login ) {
	$classes[] = 'tribe-tickets__disabled';
}
?>
<div
	<?php tribe_classes( $classes ); ?>
>
	<input
		type="number"
		class="tribe-common-h3 tribe-common-h4--min-medium tribe-tickets-quantity"
		step="1"
		min="0"
		max="<?php echo esc_attr( $max_at_a_time ); ?>"
		value="0"
		autocomplete="off"
		<?php disabled( $must_login ); ?>
	/>
</div>
