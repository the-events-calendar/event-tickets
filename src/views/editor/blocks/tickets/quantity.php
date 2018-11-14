<?php
/**
 * This template renders a Single Ticket Quantity
 *
 * @version 0.3.0-alpha
 *
 */

$ticket = $this->get( 'ticket' );
$available = $ticket->available();
$is_available = 0 !== $available;

$context = array(
	'ticket' => $ticket,
	'key' => $this->get( 'key' ),
);

?>
<div
	class="tribe-block__tickets__item__quantity"
>
	<?php if ( $is_available ) : ?>
		<?php $this->template( 'editor/blocks/tickets/quantity-remove', $context ); ?>
		<?php $this->template( 'editor/blocks/tickets/quantity-number', $context ); ?>
		<?php $this->template( 'editor/blocks/tickets/quantity-add', $context ); ?>
	<?php else : ?>
		<?php $this->template( 'editor/blocks/tickets/quantity-unavailable', $context ); ?>
	<?php endif; ?>
</div>
