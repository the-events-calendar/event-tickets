<?php
/**
 * Block: Tickets
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/tickets.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since 4.9
 * @since TBD Updated loading logic for including a renamed template.
 *
 * @version TBD
 *
 * @var Tribe__Tickets__Editor__Template $this
 */

$cart_classes        = array( 'tribe-block', 'tribe-block__tickets', 'tribe-common', 'tribe-common-l-container' );
$cart_url            = $this->get( 'cart_url' );
$has_tickets_on_sale = $this->get( 'has_tickets_on_sale' );
$is_sale_past        = $this->get( 'is_sale_past' );
$provider            = $this->get( 'provider' );
$provider_id         = $this->get( 'provider_id' );
$tickets             = $this->get( 'tickets', [] );
$tickets_on_sale     = $this->get( 'tickets_on_sale' );

// We don't display anything if there is no provider or tickets
if ( ! $provider || empty( $tickets ) ) {
	return false;
}

$html = $this->template( 'blocks/attendees/order-links', [], false );

if ( empty( $html ) ) {
	$html = $this->template( 'blocks/attendees/view-link', [], false );
}


echo $html;
?>

<form
	id="tribe-block__tickets"
	action="<?php echo esc_url( $cart_url ) ?>"
	class="<?php echo esc_attr( implode( ' ', $cart_classes ) ); ?>"
	method="post"
	enctype='multipart/form-data'
	data-provider="<?php echo esc_attr( $provider->class_name ); ?>"
	novalidate
>
	<h2 class="tribe-block__tickets__title tribe-common-h4--reg"><?php _e('Tickets', 'event-tickets'); ?></h2>
	<?php $this->template( 'blocks/tickets/commerce/fields', [ 'provider' => $provider, 'provider_id' => $provider_id ] ); ?>
	<?php if ( $has_tickets_on_sale ) : ?>
		<?php foreach ( $tickets_on_sale as $key => $ticket ) : ?>
			<?php $this->template( 'blocks/tickets/item', [ 'ticket' => $ticket, 'key' => $key ] ); ?>
		<?php endforeach; ?>
		<?php $this->template( 'blocks/tickets/footer', [ 'provider' => $provider, 'provider_id' => $provider_id, 'ticket' => $ticket ] ); ?>
	<?php else : ?>
		<?php $this->template( 'blocks/tickets/item-inactive', [ 'is_sale_past' => $is_sale_past ] ); ?>
	<?php endif; ?>
</form>
