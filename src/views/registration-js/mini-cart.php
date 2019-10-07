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
$provider = $this->get( 'provider' ) ?: tribe_get_request_var( 'provider' );

if ( empty( $provider ) ) {
	$event_keys = array_keys( $events );
	$event_key = array_shift( $event_keys );
	$provider_name     = Tribe__Tickets__Tickets::get_event_ticket_provider( $event_key );
	$provider = $provider_name::ATTENDEE_OBJECT;
}

/** @var Tribe__Tickets__Attendee_Registration__View $view */
$view = tribe( 'tickets.attendee_registration.view' );
$provider_obj        = $view->get_cart_provider( $provider );

$tickets = $this->get( 'tickets' );
// We don't display anything if there is no provider or tickets
if ( ! $provider || empty( $tickets ) ) {
	//return false;
}

$cart_classes = [
	'tribe-common',
	'tribe-tickets__mini-cart',
];

/** @var Tribe__Tickets__Commerce__Currency $currency */
$currency = tribe( 'tickets.commerce.currency' );
$tickets             = $this->get( 'tickets', [] );
$cart_url            = $this->get( 'cart_url' );
?>
<aside id="tribe-tickets__mini-cart" <?php tribe_classes( $cart_classes ); ?> data-provider="<?php echo esc_attr( $provider_obj->class_name ); ?>">
	<h3 class="tribe-common-h6 tribe-common-h5--min-medium tribe-common-h--alt tribe-tickets__mini-cart__title"><?php echo esc_html_x( 'Ticket Summary', 'Attendee registration mini-cart/ticket summary title.', 'event-tickets'); ?></h3>
		<?php foreach ( $events as $event_id => $tickets ) : ?>
			<?php foreach ( $tickets as $key => $ticket ) : ?>
				<?php if ( $provider !== $ticket['provider']->attendee_object ) : ?>
					<?php continue; ?>
				<?php endif; ?>
				<?php $currency_symbol     = $currency->get_currency_symbol( $ticket['id'], true ); ?>
				<?php $this->template(
					'blocks/tickets/item',
					[
						'ticket' => $provider_obj->get_ticket( $event_id, $ticket['id'] ),
						'key' => $key,
						'is_mini' => true,
						'currency_symbol' => $currency_symbol,
						'provider' => $provider_obj,
						'post_id' => $event_id
						]
				); ?>
			<?php endforeach; ?>
		<?php endforeach; ?>
		<?php $this->template( 'blocks/tickets/footer', [ 'is_mini' => true, 'provider' => $provider_obj ] ); ?>
</aside>

<?php foreach ( $events as $event_id => $tickets ) : ?>
	<?php
	if ( $provider !== Tribe__Tickets__Tickets::get_event_ticket_provider( $event_id )::ATTENDEE_OBJECT ) {
		continue;
	}

	$this->template(
		'registration-js/attendees/content',
		[
			'event_id' => $event_id,
			'tickets'  => $tickets,
			'provider' => $provider_obj
		]
	); ?>
<?php endforeach;
