<?php
/**
 * Block: Tickets
 * Footer
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/tickets/footer.php
 *
 * See more documentation about our views templating system.
 *
 * @link http://m.tri.be/1amp
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var WP_Post|int                        $post_id             The post object or ID.
 * @var Tribe__Tickets__Tickets            $provider            The tickets provider class.
 * @var string                             $provider_id         The tickets provider class name.
 * @var Tribe__Tickets__Ticket_Object[]    $tickets             List of tickets.
 * @var Tribe__Tickets__Ticket_Object[]    $tickets_on_sale     List of tickets on sale.
 * @var Tribe__Tickets__Commerce__Currency $currency
 */

// Bail if there are no tickets and we're not in mini context.
if (
	! $is_mini
	&& empty( $tickets_on_sale )
) {
	return;
}

?>
<div class="tribe-tickets__footer">

	<?php $this->template( 'v2/tickets/footer/return-to-cart' ); ?>

	<?php $this->template( 'v2/tickets/footer/quantity' ); ?>

	<?php $this->template( 'v2/tickets/footer/total' ); ?>

	<?php $this->template( 'v2/tickets/submit' ); ?>

</div>
