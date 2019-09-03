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
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since 4.9
 * @since TBD Tweaked logic for unlimited maximum quantity allowed.
 *
 * @version TBD
 *
 * @var Tribe__Tickets__Ticket_Object $ticket
 * @var Tribe__Tickets__Editor__Template $this
 */

$must_login = ! is_user_logged_in() && $ticket->get_provider()->login_required();

$ticket = $this->get( [ 'ticket' ] );

/** @var Tribe__Tickets__Tickets_Handler $handler */
$handler = tribe( 'tickets.handler' );

$max_quantity = $handler->get_ticket_max_purchase( $ticket->ID );
?>
<div
	class="tribe-block__tickets__item__quantity__number"
>
	<input
		type="number"
		class="tribe-ticket-quantity"
		step="1"
		min="0"
		<?php if ( -1 !== $max_quantity ) : ?>
			max="<?php echo esc_attr( $max_quantity ); ?>"
		<?php endif; ?>
		name="quantity_<?php echo absint( $ticket->ID ); ?>"
		value="0"
		autocomplete="off"
		<?php disabled( $must_login ); ?>
	/>
</div>