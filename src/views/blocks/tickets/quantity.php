<?php
/**
 * Block: Tickets
 * Quantity
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/tickets/quantity.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since 4.9
 * @version TBD
 *
 */

$ticket = $this->get( 'ticket' );
$available = $ticket->available();
$is_available = 0 !== $available;

$context = array(
	'ticket' => $ticket,
	'key' => $this->get( 'key' ),
);

$classes = [
	'tribe-common-h4',
	'tribe-block__tickets__item__quantity'
];
?>
<div
	<?php tribe_classes( $classes ); ?>
>
	<?php if ( $is_available ) : ?>
		<?php $this->template( 'blocks/tickets/quantity-remove', $context ); ?>
		<?php $this->template( 'blocks/tickets/quantity-number', $context ); ?>
		<?php $this->template( 'blocks/tickets/quantity-add', $context ); ?>
	<?php else : ?>
		<?php $this->template( 'blocks/tickets/quantity-unavailable', $context ); ?>
	<?php endif; ?>
</div>
