<?php
/**
 * Block: Tickets
 * Quantity Number
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/tickets/quantity-number.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link    {INSERT_ARTICLE_LINK_HERE}
 *
 * @since   4.9
 * @since   4.10.8 Tweaked logic for unlimited maximum quantity allowed.
 * @since   TBD The input's "max" is now always set.
 *
 * @version TBD
 *
 * @var Tribe__Tickets__Ticket_Object    $ticket
 * @var Tribe__Tickets__Editor__Template $this
 */

$must_login = ! is_user_logged_in() && $ticket->get_provider()->login_required();

$ticket = $this->get( 'ticket' );

/** @var Tribe__Tickets__Tickets_Handler $handler */
$handler = tribe( 'tickets.handler' );

$max_at_a_time = $handler->get_ticket_max_purchase( $ticket->ID );

$classes = [ 'tribe-tickets__item__quantity__number' ];

if ( $must_login ) {
	$classes[] = 'tribe-tickets__disabled';
}
?>
<div
	<?php tribe_classes( $classes ); ?>
>
	<input
		type="number"
		class="tribe-common-h3 tribe-common-h4--min-medium tribe-tickets-quantity"
		step="1"
		min="0"
		max="<?php echo esc_attr( $max_at_a_time ); ?>"
		value="0"
		autocomplete="off"
		<?php disabled( $must_login ); ?>
	/>
</div>