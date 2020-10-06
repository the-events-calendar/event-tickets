<?php
/**
 * Block: Tickets
 * Quantity Number
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/tickets/item/quantity/number.php
 *
 * See more documentation about our views templating system.
 *
 * @link    http://m.tri.be/1amp
 *
 * @since   TBD
 *
 * @version TBD
 *
 * @var Tribe__Tickets__Ticket_Object    $ticket
 * @var Tribe__Tickets__Editor__Template $this
 * @var bool                             $must_login If the user must login to purchase a ticket.
 */

/** @var Tribe__Tickets__Tickets_Handler $handler */
$handler = tribe( 'tickets.handler' );

$max_at_a_time = $handler->get_ticket_max_purchase( $ticket->ID );

$classes = [
	'tribe-tickets__item__quantity__number',
	'tribe-tickets__disabled' => ! empty( $must_login ),
];

?>
<div <?php tribe_classes( $classes ); ?>>
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
