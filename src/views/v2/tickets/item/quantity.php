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
 * @link    https://m.tri.be/1amp Help article for RSVP & Ticket template files.
 *
 * @since   TBD
 *
 * @version TBD
 *
 * @var Tribe__Tickets__Editor__Template $this          The template object.
 * @var Tribe__Tickets__Ticket_Object    $ticket        The ticket object.
 * @var bool                             $is_mini       If the template is in "mini cart" context.
 * @var int                              $key           Ticket Item index.
 * @var int                              $max_at_a_time The maximum quantity able to be purchased in a single Add to Cart action.
 */

// Bail if it's "mini cart" context.
if ( ! empty( $is_mini ) ) {
	return;
}

$classes = [
	'tribe-common-h4',
	'tribe-tickets__tickets-item-quantity',
];

$context = [
	'ticket'        => $ticket,
	'key'           => $key,
	'max_at_a_time' => $max_at_a_time,
];
?>
<div <?php tribe_classes( $classes ); ?>>
	<?php if ( 0 !== $max_at_a_time ) : ?>
		<?php $this->template( 'v2/tickets/item/quantity/remove', $context ); ?>
		<?php $this->template( 'v2/tickets/item/quantity/number', $context ); ?>
		<?php $this->template( 'v2/tickets/item/quantity/add', $context ); ?>
	<?php else : ?>
		<?php $this->template( 'v2/tickets/item/quantity/unavailable', $context ); ?>
	<?php endif; ?>
</div>
