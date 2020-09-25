<?php
/**
 * Block: Tickets
 * Single Ticket Item
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/tickets/item.php
 *
 * See more documentation about our views templating system.
 *
 * @link    http://m.tri.be/1amp
 *
 * @since   TBD
 *
 * @version TBD
 *
 * @var Tribe__Tickets__Ticket_Object      $ticket
 * @var Tribe__Tickets__Tickets            $provider    The tickets provider class.
 * @var string                             $provider_id The tickets provider class name.
 * @var Tribe__Tickets__Commerce__Currency $currency
 * @var int                                $key         The ticket key.
 * @var bool                               $is_mini     True if it's in mini cart context.
 * @var bool                               $is_modal    True if it's in modal context.
 */

/** @var Tribe__Tickets__Tickets_Handler $tickets_handler */
$tickets_handler = tribe( 'tickets.handler' );

$context = [
	'ticket'          => $ticket,
	'key'             => $this->get( 'key' ),
	'currency_symbol' => $currency->get_currency_symbol( $ticket->ID, true ),
];

if (
	empty( $provider )
	|| $ticket->provider_class !== $provider->class_name
) {
	return false;
}

$has_shared_cap = $tickets_handler->has_shared_capacity( $ticket );
$has_suffix     = ! empty( $ticket->price_suffix );

$classes = [
	'tribe-tickets__item',
	'tribe-tickets__item__disabled'     => ! empty( $must_login ),
	'tribe-tickets__item--price-suffix' => $has_suffix,
	get_post_class( '', $ticket->ID ),
];

$data_available      = 0 === $tickets_handler->get_ticket_max_purchase( $ticket->ID ) ? 'false' : 'true';
$data_has_shared_cap = $has_shared_cap ? 'true' : 'false';
$ticket_item_id      = 'tribe-';
$ticket_item_id     .= ! empty( $is_modal ) ? 'modal' : 'block';
$ticket_item_id     .= '-tickets-item-' . $ticket->ID;
?>
<div
	id="<?php echo esc_attr( $ticket_item_id ); ?>"
	<?php tribe_classes( $classes ); ?>
	data-ticket-id="<?php echo esc_attr( $ticket->ID ); ?>"
	data-available="<?php echo esc_attr( $data_available ); ?>"
	data-has-shared-cap="<?php echo esc_attr( $data_has_shared_cap ); ?>"
	<?php if ( $has_shared_cap ) : ?>
		data-shared-cap="<?php echo esc_attr( get_post_meta( $post_id, $tickets_handler->key_capacity, true ) ); ?>"
	<?php endif; ?>
>

	// @todo Convert this into an action.
	<?php //$this->template( 'v2/modal/item/remove', $context ); ?>

	<?php $this->template( 'v2/tickets/item/content', $context ); ?>

	<?php $this->template( 'v2/tickets/item/quantity', $context ); ?>

	<?php $this->template( 'v2/tickets/item/quantity-mini', $context ); ?>

	// @todo Convert this into an action.
	<?php //$this->template( 'v2/modal/item/total', $context ); ?>

	<?php $this->template( 'v2/tickets/item/opt-out', $context ); ?>

	// @todo Convert this into an action.
	<?php //$this->template( 'v2/modal/item/opt-out', $context ); ?>

</div>
