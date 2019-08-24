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
 * @since TBD
 *
 * @version TBD
 *
 */

$cart_classes        = array( 'tribe-modal-cart', 'tribe-modal-cart__tickets' );

// We don't display anything if there is no provider or tickets
if ( ! $provider || empty( $tickets ) ) {
	return false;
}

?>

<form
	id="tribe-modal-cart__tickets"
	action="<?php echo esc_url( $cart_url ) ?>"
	class="<?php echo esc_attr( implode( ' ', $cart_classes ) ); ?>"
	method="post"
	enctype='multipart/form-data'
	data-provider="<?php echo esc_attr( $provider->class_name ); ?>"
	novalidate
>
	<?php $template_obj->template( 'blocks/tickets/commerce/fields', array( 'provider' => $provider, 'provider_id' => $provider_id ) ); ?>

	<?php if ( $has_tickets_on_sale ) : ?>
		<?php foreach ( $tickets_on_sale as $key => $ticket ) : ?>
			<?php $template_obj->template( 'blocks/tickets/item', array( 'ticket' => $ticket, 'key' => $key, 'is_modal' => true ) ); ?>
		<?php endforeach; ?>
		<?php //$this->template( 'blocks/tickets/submit', array( 'provider' => $provider, 'provider_id' => $provider_id, 'ticket' => $ticket ) ); ?>
	<?php endif; ?>

</form>
<div class="tribe-cart-totals">
	<span class="total-qty-wrap">
		<span class="total-qty-label">Total Qty: </span>
		<span class="total-qty"></span>
	</span>
	<span class="total-amount-wrap">
		<span class="total-amount-label">Total: </span>
		<span class="price-currency-symbol">$</span><span class="total-amount"></span>
	</span>
</div>