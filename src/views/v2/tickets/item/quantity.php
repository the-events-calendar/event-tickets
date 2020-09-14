<?php
/**
 * Block: Tickets
 * Quantity
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/tickets/item/quantity.php
 *
 * See more documentation about our views templating system.
 *
 * @link http://m.tri.be/1amp
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var Tribe__Tickets__Ticket_Object $ticket The ticket object.
 * @var bool                          $is_mini   If the template is in "mini cart" context.
 */

// Bail if it's "mini cart" context.
if ( ! empty( $is_mini ) ) {
	return;
}

/** @var Tribe__Tickets__Tickets_Handler $tickets_handler */
$tickets_handler = tribe( 'tickets.handler' );
$available       = $tickets_handler->get_ticket_max_purchase( $ticket->ID );

$context = [
	'ticket' => $ticket,
	'key'    => $this->get( 'key' ),
];

$classes = [
	'tribe-common-h4',
	'tribe-tickets__item__quantity',
];
?>
<div <?php tribe_classes( $classes ); ?>>
	<?php if ( 0 !== $available ) : ?>
		<?php $this->template( 'v2/tickets/item/quantity/remove', $context ); ?>
		<?php $this->template( 'v2/tickets/item/quantity/number', $context ); ?>
		<?php $this->template( 'v2/tickets/item/quantity/add', $context ); ?>
	<?php else : ?>
		<?php $this->template( 'v2/tickets/item/quantity/unavailable', $context ); ?>
	<?php endif; ?>
</div>
