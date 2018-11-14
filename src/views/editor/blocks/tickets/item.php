<?php
/**
 * This template renders one Ticket Container it will
 * be repeated for as many ticket are to be displayed
 *
 * @version 0.3.0-alpha
 *
 */
$post_id = $this->get( 'post_id' );
$ticket  = $this->get( 'ticket' );
$classes = array(
	'tribe-block__tickets__item',
);

$context = array(
	'ticket' => $ticket,
	'key'    => $this->get( 'key' ),
);
?>
<div
	id="tribe-block-tickets-item-<?php echo esc_attr( $ticket->ID ); ?>"
	class="<?php echo implode( ' ', get_post_class( $classes, $ticket->ID ) ); ?>"
	data-ticket-id="<?php echo esc_attr( $ticket->ID ); ?>"
	data-available="<?php echo ( 0 === $ticket->available() ) ? 'false' : 'true'; ?>"
>
	<input type="hidden" name="product_id[]" value="<?php echo esc_attr( $ticket->ID ); ?>" />
	<?php $this->template( 'editor/blocks/tickets/icon', $context ); ?>
	<?php $this->template( 'editor/blocks/tickets/content', $context ); ?>
	<?php $this->template( 'editor/blocks/tickets/extra', $context ); ?>
	<?php $this->template( 'editor/blocks/tickets/quantity', $context ); ?>
</div>