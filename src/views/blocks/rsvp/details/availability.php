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
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since 4.9.3
 * @version 4.9.4
 *
 */

$remaining_tickets = $ticket->remaining();
$is_unlimited = -1 === $remaining_tickets;
?>
<div class="tribe-block__rsvp__availability">
	<?php if ( ! $ticket->is_in_stock() ) : ?>
		<span class="tribe-block__rsvp__no-stock"><?php esc_html_e( 'Out of stock!', 'event-tickets' ); ?></span>
	<?php elseif ( ! $is_unlimited ) : ?>
		<span class="tribe-block__rsvp__quantity"><?php echo $ticket->remaining(); ?> </span>
		<?php esc_html_e( 'remaining', 'event-tickets' ) ?>
	<?php else : ?>
		<span class="tribe-block__rsvp__unlimited"><?php esc_html_e( 'Unlimited', 'event-tickets' ); ?></span>
	<?php endif; ?>
</div>
