<?php
/**
 * This template renders the tickets
 *
 * @version TBD
 *
 */
$post_id      = $this->get( 'post_id' );
$tickets      = $this->get( 'tickets', array() );
$provider     = $this->get( 'provider' );
$provider_id  = $this->get( 'provider_id' );
$cart_url     = $this->get( 'cart_url' );
$cart_classes = array( 'tribe-block', 'tribe-block__tickets' );

// We don't display anything if there is not provider
if ( ! $provider ) {
	return false;
}
?>

<?php $this->template( 'editor/blocks/attendees/order-links', array( 'type' => 'ticket' ) ); ?>

<form
	id="tribe-block__tickets"
	action="<?php echo esc_url( $cart_url ) ?>"
	class="<?php echo esc_attr( implode( ' ', $cart_classes ) ); ?>"
	method="post"
	enctype='multipart/form-data'
	data-provider="<?php echo esc_attr( $provider->class_name ); ?>"
	novalidate
>
	<?php $this->template( 'editor/blocks/tickets/commerce/fields', array( 'provider' => $provider, 'provider_id' => $provider_id ) ); ?>
	<?php foreach ( $tickets as $key => $ticket ) : ?>
		<?php $this->template( 'editor/blocks/tickets/item', array( 'ticket' => $ticket, 'key' => $key ) ); ?>
	<?php endforeach; ?>
	<?php if ( 0 < count( $tickets ) ) : ?>
		<?php $this->template( 'editor/blocks/tickets/submit', array( 'provider' => $provider, 'provider_id' => $provider_id, 'ticket' => $ticket ) ); ?>
	<?php endif; ?>
</form>