<?php
/**
 * Block: Tickets
 * Single Ticket Item
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/tickets/item.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link    {INSERT_ARTICLE_LINK_HERE}
 *
 * @since   4.9
 * @since   4.11.0 Add modal only fields
 * @since   TBD Corrected amount of available/remaining tickets.
 *
 * @version TBD
 */
$classes  = [ 'tribe-tickets__item' ];

/** @var Tribe__Tickets__Tickets $provider */
$provider = $this->get( 'provider' );

/** @var Tribe__Tickets__Ticket_Object $ticket */
$ticket = $this->get( 'ticket' );

if ( empty( $ticket->ID ) ) {
	return;
}

/** @var Tribe__Tickets__Tickets_Handler $tickets_handler */
$tickets_handler = tribe( 'tickets.handler' );

$modal           = $this->get( 'is_modal' );
$mini            = $this->get( 'is_mini' );
$post_id         = $this->get( 'post_id' );
$currency_symbol = $this->get( 'currency_symbol' );
$context         = [
	'ticket'          => $ticket,
	'key'             => $this->get( 'key' ),
	'is_modal'        => $modal,
	'is_mini'         => $mini,
	'currency_symbol' => $currency_symbol,
	'post_id'         => $post_id,
	'provider'        => $provider,
];

if (
	empty( $provider )
	|| $ticket->provider_class !== $provider->class_name
) {
	return false;
}

$must_login = ! is_user_logged_in() && $ticket->get_provider()->login_required();
if ( $must_login ) {
	$classes[] = 'tribe-tickets__item__disabled';
}

$has_shared_cap = $tickets_handler->has_shared_capacity( $ticket );

?>
<div
	id="tribe-<?php echo $modal ? 'modal' : 'block'; ?>-tickets-item-<?php echo esc_attr( $ticket->ID ); ?>"
	<?php tribe_classes( get_post_class( $classes, $ticket->ID ) ); ?>
	data-ticket-id="<?php echo esc_attr( $ticket->ID ); ?>"
	data-available="<?php echo ( 0 === $tickets_handler->get_ticket_max_purchase( $ticket->ID ) ) ? 'false' : 'true'; ?>"
	data-has-shared-cap="<?php echo $has_shared_cap ? 'true' : 'false'; ?>"
	<?php if ( $has_shared_cap) : ?>
		data-shared-cap="<?php echo $ticket->capacity(); ?>"
	<?php endif; ?>

>
	<?php if ( true === $modal ) : ?>
		<?php $this->template( 'modal/item-remove', $context ); ?>
	<?php endif ?>

	<?php $this->template( 'blocks/tickets/content', $context ); ?>

	<?php if ( true !== $mini ) : ?>
		<?php $this->template( 'blocks/tickets/quantity', $context ); ?>
	<?php else: ?>
		<div class="tribe-ticket-quantity">0</div>
	<?php endif; ?>

	<?php if ( true === $modal || true === $mini ) : ?>
		<?php $this->template( 'modal/item-total', $context ); ?>
	<?php endif; ?>

	<?php if ( ! $modal && ! $mini ) : ?>
		<?php $this->template( 'blocks/rsvp/form/opt-out', $context ); ?>
	<?php elseif ( true === $modal ): ?>
		<?php $this->template( 'blocks/tickets/opt-out-hidden', $context ); ?>
	<?php endif; ?>
</div>
