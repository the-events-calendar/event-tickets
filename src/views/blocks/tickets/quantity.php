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
 * @version 4.9.4
 *
 */

$ticket = $this->get( 'ticket' );
$available = $ticket->available();
$is_available = 0 !== $available;

$context = array(
	'ticket' => $ticket,
	'key' => $this->get( 'key' ),
);

$container_classes = [ 'tribe-block__tickets__item__quantity', 'tribe-common-h4' ];
$container_classes = implode( ' ', $container_classes);
?>
<div
	class="<?php echo esc_attr( $container_classes ); ?>"
>
	<?php if ( $is_available ) : ?>
		<?php $this->template( 'blocks/tickets/quantity-remove', $context ); ?>
		<?php $this->template( 'blocks/tickets/quantity-number', $context ); ?>
		<?php $this->template( 'blocks/tickets/quantity-add', $context ); ?>
	<?php else : ?>
		<?php $this->template( 'blocks/tickets/quantity-unavailable', $context ); ?>
	<?php endif; ?>
</div>
