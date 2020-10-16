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
 * @link    https://m.tri.be/1amp Help article for RSVP & Ticket template files.
 *
 * @since   TBD
 *
 * @version TBD
 *
 * @var Tribe__Tickets__Ticket_Object[] $active_rsvps    The RSVPs.
 * @var Tribe__Tickets__Tickets_Handler $tickets_handler The Tickets Handler instance.
 * @var Tribe__Tickets__Ticket_Object   $ticket          The Ticket Object.
 * @var WP_Post|int                     $post_id         The post object or ID.
 * @var Tribe__Tickets__Tickets         $provider        The tickets provider class.
 * @var string                          $provider_id     The tickets provider class name.
 * @var int                             $key             The ticket key.
 * @var bool                            $has_shared_cap  True if ticket has shared capacity.
 * @var bool                            $is_mini         True if it's in mini cart context.
 * @var bool                            $is_modal        True if it's in modal context.
 */

if (
	empty( $provider )
	|| $ticket->provider_class !== $provider->class_name
) {
	return false;
}

/* @var Tribe__Tickets__Privacy $privacy */
$privacy = tribe( 'tickets.privacy' );

/** @var Tribe__Tickets__Tickets_Handler $tickets_handler */
$tickets_handler = tribe( 'tickets.handler' );

$context = [
	'ticket'    => $ticket,
	'key'       => $this->get( 'key' ),
	'privacy'   => $privacy,
	'max_at_a_time' => $tickets_handler->get_ticket_max_purchase( $ticket->ID ),
];

$has_suffix = ! empty( $ticket->price_suffix );

$classes = [
	'tribe-tickets__item',
	'tribe-tickets__item__disabled'     => ! empty( $must_login ),
	'tribe-tickets__item--price-suffix' => $has_suffix,
	get_post_class( '', $ticket->ID ),
];

$ticket_item_id = 'tribe-';
$ticket_item_id .= ! empty( $is_modal ) ? 'modal' : 'block';
$ticket_item_id .= '-tickets-item-' . $ticket->ID;
?>
<div
	id="<?php echo esc_attr( $ticket_item_id ); ?>"
	<?php tribe_classes( $classes ); ?>
	data-ticket-id="<?php echo esc_attr( $ticket->ID ); ?>"
	data-available="<?php echo esc_attr( $this->get( 'data_available' ) ); ?>"
	data-has-shared-cap="<?php echo esc_attr( $this->get( 'data_has_shared_cap' ) ); ?>"
	<?php if ( $this->get( 'has_shared_cap' ) ) : ?>
		data-shared-cap="<?php echo esc_attr( get_post_meta( $post_id, $tickets_handler->key_capacity, true ) ); ?>"
	<?php endif; ?>
>

	<?php $this->template( 'v2/tickets/item/content', $context ); ?>

	<?php $this->template( 'v2/tickets/item/quantity', $context ); ?>

	<?php $this->template( 'v2/tickets/item/quantity-mini', $context ); ?>

	<?php $this->template( 'v2/tickets/item/opt-out', $context ); ?>

</div>
