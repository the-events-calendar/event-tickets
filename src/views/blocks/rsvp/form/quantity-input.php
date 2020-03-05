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
 * @link    {INSERT_ARTICLE_LINK_HERE}
 *
 * @since   4.9
 * @since   4.11.1 Corrected amount of available/remaining tickets. Removed unused `data-remaining` attribute.
 * @since   TBD The input's "max" is now always set and remove unused `data-remaining` attribute.
 *
 * @version TBD
 */

/** @var Tribe__Tickets__RSVP $rsvp */
$rsvp = tribe( 'tickets.rsvp' );

$must_login = ! is_user_logged_in() && $rsvp->login_required();

/** @var Tribe__Tickets__Ticket_Object $ticket */
if ( empty( $ticket->ID ) ) {
	return;
}

/** @var Tribe__Tickets__Tickets_Handler $tickets_handler */
$tickets_handler = tribe( 'tickets.handler' );

$max_at_a_time = $tickets_handler->get_ticket_max_purchase( $ticket->ID );
?>
<input
	type="number"
	name="quantity_<?php echo absint( $ticket->ID ); ?>"
	class="tribe-tickets-quantity"
	step="1"
	min="1"
	value="1"
	required
	max="<?php echo esc_attr( $max_at_a_time ); ?>"
	<?php disabled( $must_login ); ?>
/>