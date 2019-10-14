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
 * @since 4.10.8 Updated loading logic for including a renamed template.
 * @since 4.10.10 - Removed initial check for tickets.
 *
 * @version 4.10.10
 *
 * @var Tribe__Tickets__Editor__Template $this
 */

$post_id             = $this->get( 'post_id' );
$tickets             = $this->get( 'tickets', array() );
$provider            = $this->get( 'provider' );
$provider_id         = $this->get( 'provider_id' );
$cart_url            = $this->get( 'cart_url' );
$tickets_on_sale     = $this->get( 'tickets_on_sale' );
$has_tickets_on_sale = $this->get( 'has_tickets_on_sale' );
$is_sale_past        = $this->get( 'is_sale_past' );
$cart_classes        = array( 'tribe-block', 'tribe-block__tickets' );

// We don't display anything if there is no provider or tickets
if ( ! $provider ) {
	return false;
}

$html = $this->template( 'blocks/attendees/order-links', [], false );

if ( empty( $html ) ) {
	$html = $this->template( 'blocks/attendees/view-link', [], false );;
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
	<?php $this->template( 'blocks/tickets/commerce/fields', array( 'provider' => $provider, 'provider_id' => $provider_id ) ); ?>
	<?php if ( $has_tickets_on_sale ) : ?>
		<?php foreach ( $tickets_on_sale as $key => $ticket ) : ?>
			<?php $this->template( 'blocks/tickets/item', array( 'ticket' => $ticket, 'key' => $key ) ); ?>
		<?php endforeach; ?>
		<?php $this->template( 'blocks/tickets/submit', array( 'provider' => $provider, 'provider_id' => $provider_id, 'ticket' => $ticket ) ); ?>
	<?php else : ?>
		<?php $this->template( 'blocks/tickets/item-inactive', array( 'is_sale_past' => $is_sale_past ) ); ?>
	<?php endif; ?>
</form>
