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
 * @since TBD add modal only fields
 *
 * @version TBD
 *
 */
$classes  = [ 'tribe-tickets__item' ];
$provider = $this->get( 'provider' );
$ticket   = $this->get( 'ticket' );
$modal    = $this->get( 'is_modal' );
$currency_symbol = $this->get( 'currency_symbol' );
$context  = [
	'ticket'  => $ticket,
	'key'     => $this->get( 'key' ),
	'is_modal' => $modal,
	'currency_symbol' => $currency_symbol,
];

if (
	empty( $provider )
	|| $ticket->provider_class !== $provider->class_name
) {
	return false;
}

$must_login = ! is_user_logged_in() && $ticket->get_provider()->login_required();
if ( $must_login ) {
	$classes[] = 'tribe-tickets__item__disabled';
}
?>
<div
	id="tribe-block-tickets<?php echo $modal ? '-modal' : ''; ?>-item-<?php echo esc_attr( $ticket->ID ); ?>"
	<?php tribe_classes( get_post_class( $classes, $ticket->ID ) ); ?>
	data-ticket-id="<?php echo esc_attr( $ticket->ID ); ?>"
	data-available="<?php echo ( 0 === $ticket->available() ) ? 'false' : 'true'; ?>"
>
	<input type="hidden" name="product_id[]" value="<?php echo esc_attr( $ticket->ID ); ?>" />
	<?php if ( true === $modal ) { $this->template( 'modal/item-remove', $context ); } ?>
	<?php $this->template( 'blocks/tickets/content', $context ); ?>
	<?php $this->template( 'blocks/tickets/quantity', $context ); ?>
	<?php if ( true === $modal ) { $this->template( 'modal/item-total', $context ); } ?>
</div>
