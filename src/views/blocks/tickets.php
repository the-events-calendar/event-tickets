<?php
/**
 * Block: Tickets
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/tickets.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTCILE_LINK_HERE}
 *
 * @version 4.9
 *
 */

$post_id      = $this->get( 'post_id' );
$tickets      = $this->get( 'tickets', array() );
$provider     = $this->get( 'provider' );
$provider_id  = $this->get( 'provider_id' );
$cart_url     = $this->get( 'cart_url' );
$cart_classes = array( 'tribe-block', 'tribe-block__tickets' );

// We don't display anything if there is no provider or tickets
if ( ! $provider || empty( $tickets ) ) {
	return false;
}

// Get tickets on sale
$tickets_on_sale = array();

foreach ( $tickets as $ticket ) {
	if ( tribe_events_ticket_is_on_sale( $ticket ) ) {
		$tickets_on_sale[] = $ticket;
	}
}

$has_tickets_on_sale = ! empty( $tickets_on_sale );

if ( ! $has_tickets_on_sale ) {
	$sale_past = ! empty( $tickets );
	$timestamp = current_time( 'timestamp' );

	foreach ( $tickets as $ticket ) {
		$sale_past = ( $sale_past && $ticket->date_is_later( $timestamp ) );
	}
}
?>

<?php $this->template( 'blocks/attendees/order-links', array( 'type' => 'ticket' ) ); ?>

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
		<?php $this->template( 'blocks/tickets/item-inactive', array( 'sale_past' => $sale_past ) ); ?>
	<?php endif; ?>
</form>
