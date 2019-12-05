<?php
/**
 * Block: Tickets
 * Extra column, available
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/tickets/extra-available.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since 4.9.3
 * @version TBD
 *
 */

$ticket    = $this->get( 'ticket' );
$available = $ticket->available();

if ( -1 === $available ) {
	return;
}

$post_id   = $this->get( 'post_id' );
$threshold = tribe( 'settings.manager' )::get_option( 'ticket-display-tickets-left-threshold', 0 );

/**
 * Overwrites the threshold to display "# tickets left".
 *
 * @param int   $threshold Stock threshold to trigger display of "# tickets left"
 * @param array $data      Ticket data.
 * @param int   $post_id   WP_Post/Event ID.
 *
 * @since TBD
 */
$threshold = absint( apply_filters( 'tribe_display_tickets_block_tickets_left_threshold', $threshold, $post_id ) );
$available = $ticket->available();
?>
<div
	class="tribe-common-b3 tribe-tickets__item__extra__available"
>
	<?php if ( -1 !== $available && $threshold >= $available ) : ?>
		<?php $this->template( 'blocks/tickets/extra-available-quantity', [ 'ticket' => $ticket ] ); ?>
	<?php endif; ?>
</div>
