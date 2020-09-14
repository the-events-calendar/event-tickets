<?php
/**
 * AR: Mini-Cart
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/attendee-registration/mini-cart.php
 *
 * See more documentation about our views templating system.
 *
 * @link http://m.tri.be/1amp
 *
 * @since   TBD
 *
 * @version TBD
 *
 * @var mixed                              $provider The e-commerce provider.
 * @var Tribe__Tickets__Commerce__Currency $currency
 */

$cart_provider  = $this->get_cart_provider( $provider );
$provider_class = ! empty( $cart_provider ) ? $cart_provider->class_name : '';

$cart_classes = [
	'tribe-common',
	'event-tickets',
	'tribe-tickets__mini-cart',
];

?>
<aside
	<?php tribe_classes( $cart_classes ); ?>
	id="tribe-tickets__mini-cart"
	data-provider="<?php echo esc_attr( $provider_class ); ?>"
>
	<?php

	$this->template( 'v2/attendee-registration/mini-cart/title' );

	foreach ( $events as $post_id => $tickets ) :

		foreach ( $tickets as $key => $ticket ) :
			if ( $provider_class !== $ticket['provider']->class_name ) {
				continue;
			}
			$currency_symbol = $currency->get_currency_symbol( $ticket['id'], true );
			$this->template(
				'v2/tickets/item',
				[
					'ticket'          => $cart_provider->get_ticket( $post_id, $ticket['id'] ),
					'key'             => $key,
					'is_mini'         => true,
					'currency_symbol' => $currency_symbol,
					'provider'        => $cart_provider,
					'post_id'         => $post_id,
				]
			);
		endforeach;
	endforeach;

	$this->template( 'v2/tickets/footer', [ 'is_mini' => true, 'provider' => $cart_provider ] );

	?>
</aside>

<?php foreach ( $events as $post_id => $tickets ) : ?>
	<?php
	$event_provider = Tribe__Tickets__Tickets::get_event_ticket_provider_object( $post_id );

	if (
		empty( $event_provider )
		|| $provider_class !== $event_provider->class_name
	) {
		continue;
	}

	$this->template(
		'v2/attendee-registration/content/attendees/content',
		[
			'post_id'  => $post_id,
			'tickets'  => $tickets,
			'provider' => $cart_provider,
		]
	);
	?>
<?php endforeach; ?>
