<?php
/**
 * Block: RSVP
 * Details Availability
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/rsvp/details/availability.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link  {INSERT_ARTICLE_LINK_HERE}
 *
 * @since 4.9.3
 * @since TBD Corrected amount of available/remaining tickets.
 *
 * @version TBD
 */

/** @var Tribe__Tickets__Ticket_Object $ticket */
if ( empty( $ticket->ID ) ) {
	return;
}

/** @var Tribe__Tickets__Tickets_Handler $tickets_handler */
$tickets_handler = tribe( 'tickets.handler' );

$available = $tickets_handler->get_ticket_max_purchase( $ticket->ID );

?>
<div class="tribe-block__rsvp__availability">
	<?php if ( ! $ticket->is_in_stock() ) : ?>
		<span class="tribe-block__rsvp__no-stock"><?php esc_html_e( 'Out of stock!', 'event-tickets' ); ?></span>
	<?php elseif ( -1 !== $available ) : ?>
		<span class="tribe-block__rsvp__quantity"><?php echo esc_html( $available ); ?> </span>
		<?php esc_html_e( 'remaining', 'event-tickets' ) ?>
	<?php else : ?>
		<span class="tribe-block__rsvp__unlimited"><?php esc_html_e( 'Unlimited', 'event-tickets' ); ?></span>
	<?php endif; ?>
</div>
