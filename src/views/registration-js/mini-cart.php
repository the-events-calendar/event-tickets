<?php
/**
 * AR: Mini-Cart
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/registration-js/mini-cart.php
 *
 * @since TBD
 *
 * @version TBD
 *
 */
$provider = $this->get( 'provider' );
if ( empty( $provider ) ) {
	$provider = tribe_get_request_var( 'provider' );
}
$tickets = $this->get( 'tickets' );
// We don't display anything if there is no provider or tickets
if ( ! $provider || empty( $tickets ) ) {
	//return false;
}

$cart_classes = [
	'tribe-mini-cart',
	'tribe-common',
];


/** @var Tribe__Tickets__Commerce__Currency $currency */
$currency = tribe( 'tickets.commerce.currency' );
/** @var Tribe__Tickets__Attendee_Registration__View $view */
$view = tribe( 'tickets.attendee_registration.view' );
$provider_obj = $view->get_cart_provider( $provider );

?>
<form
	id="tribe-mini-cart"
	action="<?php echo esc_url( $cart_url ) ?>"
	<?php tribe_classes( $cart_classes ); ?>
	method="post"
	enctype='multipart/form-data'
	data-provider="<?php echo esc_attr( $provider_obj->class_name ); ?>"
	autocomplete="off"
	novalidate
>
	<input
		type="hidden"
		name="provider"
		value="<?php echo esc_attr( $provider_obj->class_name ); ?>"
		class="tribe-tickets-provider"
	>
	<?php if ( $has_tickets_on_sale ) : ?>
		<?php foreach ( $tickets_on_sale as $key => $ticket ) : ?>
		<?php $currency_symbol     = $currency->get_currency_symbol( $ticket->ID, true ); ?>
			<?php $this->template( 'blocks/tickets/item', [ 'ticket' => $ticket, 'key' => $key, 'is_mini' => true, 'currency_symbol' => $currency_symbol ] ); ?>
		<?php endforeach; ?>
	<?php endif; ?>
	<?php $this->template( 'blocks/tickets/footer', [ 'is_mini' => true ] ); ?>
</form>

<?php foreach ( $events as $event_id => $tickets ) : ?>
	<?php
	$this->template(
		'registration-js/attendees/content',
		[
			'event_id' => $event_id,
			'tickets'  => $tickets,
			'provider' => $provider_obj
		]
	); ?>
<?php endforeach;
