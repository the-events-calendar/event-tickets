<?php
/**
 * Block: Tickets
 * Single Ticket Item
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/tickets/item.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since 4.9
 * @version 4.9.4
 *
 */

$post_id  = $this->get( 'post_id' );
$ticket   = $this->get( 'ticket' );
$provider = $this->get( 'provider' );
$modal = $this->get( 'is_modal' );

$classes  = array(
	'tribe-block__tickets__item',
);

$context = array(
	'ticket' => $ticket,
	'key'    => $this->get( 'key' ),
);

if (
	empty( $provider )
	|| $ticket->provider_class !== $provider->class_name
) {
	return false;
}
?>
<div
	id="tribe-block-tickets-item-<?php echo esc_attr( $ticket->ID ); ?>"
	class="<?php echo esc_attr( implode( ' ', get_post_class( $classes, $ticket->ID ) ) ); ?>"
	data-ticket-id="<?php echo esc_attr( $ticket->ID ); ?>"
	data-available="<?php echo ( 0 === $ticket->available() ) ? 'false' : 'true'; ?>"
>
	<input type="hidden" name="product_id[]" value="<?php echo esc_attr( $ticket->ID ); ?>" />
	<?php if ( isset( $modal ) ) { $this->template( 'modal/ticket-removal', $context ); } ?>
	<?php $this->template( 'blocks/tickets/icon', $context ); ?>
	<?php $this->template( 'blocks/tickets/content', $context ); ?>
	<?php $this->template( 'blocks/tickets/extra', $context ); ?>
	<?php $this->template( 'blocks/tickets/quantity', $context ); ?>
	<?php if ( isset( $modal ) ) { $this->template( 'modal/ticket-total', $context ); } ?>
</div>
