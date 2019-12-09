<?php
/**
 * Block: RSVP
 * Form Quantity Input
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/rsvp/form/quantity-input.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since 4.9
 * @since TBD Corrected amount of available/remaining tickets.
 *
 * @version TBD
 */
$must_login = ! is_user_logged_in() && tribe( 'tickets.rsvp' )->login_required();

/** @var Tribe__Tickets__Ticket_Object $ticket */
if ( empty( $ticket->ID ) ) {
	return;
}

/** @var Tribe__Tickets__Tickets_Handler $tickets_handler */
$tickets_handler = tribe( 'tickets.handler' );

$available = $tickets_handler->get_ticket_max_purchase( $ticket->ID );
?>
<input
	type="number"
	name="quantity_<?php echo absint( $ticket->ID ); ?>"
	class="tribe-tickets-quantity"
	step="1"
	min="1"
	value="1"
	required
	data-remaining="<?php echo esc_attr( $available ); ?>"
	<?php if ( -1 !== $available ) : ?>
		max="<?php echo esc_attr( $available ); ?>"
	<?php endif; ?>
	<?php disabled( $must_login ); ?>
/>