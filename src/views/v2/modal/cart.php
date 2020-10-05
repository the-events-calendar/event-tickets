<?php
/**
 * Modal: Cart
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/modal/cart.php
 *
 * See more documentation about our views templating system.
 *
 * @link http://m.tri.be/1amp
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var Tribe__Tickets__Ticket_Object[]    $tickets             List of tickets.
 * @var Tribe__Tickets__Ticket_Object[]    $tickets_on_sale     List of tickets on sale.
 * @var Tribe__Tickets__Commerce__Currency $currency
 * @var bool                               $is_mini             True if it's in mini cart context.
 * @var bool                               $is_modal            True if it's in modal context.
 * @var Tribe__Tickets__Tickets            $provider            The tickets provider class.
 * @var string                             $provider_id         The tickets provider class name.
 * @var string                             $cart_url            The cart URL.
 */

// We don't display anything if there is no provider or tickets.
if ( ! $provider || empty( $tickets ) ) {
	return;
}

$cart_classes = [
	'tribe-modal-cart',
	'tribe-modal__cart',
	'tribe-common',
	'event-tickets',
];

?>
<div
	id="tribe-modal__cart"
	action="<?php echo esc_url( $cart_url ); ?>"
	<?php tribe_classes( $cart_classes ); ?>
	method="post"
	enctype='multipart/form-data'
	data-provider="<?php echo esc_attr( $provider->class_name ); ?>"
	autocomplete="off"
	novalidate
>
	<?php

	$this->template(
		'v2/tickets/commerce/fields',
		[
			'provider'    => $provider,
			'provider_id' => $provider_id,
		]
	);

	if ( $has_tickets_on_sale ) :
		foreach ( $tickets_on_sale as $key => $ticket ) :
			$currency_symbol = $currency->get_currency_symbol( $ticket->ID, true );
			$this->template(
				'v2/tickets/item',
				[
					'ticket'          => $ticket,
					'key'             => $key,
					'is_modal'        => true,
					'currency_symbol' => $currency_symbol,
					'must_login'      => $must_login,
				]
			);
		endforeach;
	endif;

	$this->template( 'v2/components/loader/loader' );

	$this->template( 'v2/tickets/footer', [ 'is_modal' => true ] );
	?>
</div>
