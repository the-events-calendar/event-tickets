<?php
/**
 * Block: RSVP
 * Form Quantity Input
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp/ari/sidebar/quantity/input.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link    {INSERT_ARTICLE_LINK_HERE}
 *
 * @var bool $must_login Whether the user has to login to RSVP or not.
 * @var Tribe__Tickets__Ticket_Object $rsvp The rsvp ticket object.
 *
 * @since   4.12.3
 *
 * @version 4.12.3
 */

/** @var Tribe__Tickets__Ticket_Object $rsvp */
if ( empty( $rsvp->ID ) ) {
	return;
}

/** @var Tribe__Tickets__Tickets_Handler $tickets_handler */
$tickets_handler = tribe( 'tickets.handler' );

$max_at_a_time = $tickets_handler->get_ticket_max_purchase( $rsvp->ID );
?>
<input
	type="number"
	name="quantity_<?php echo absint( $rsvp->ID ); ?>"
	class="tribe-common-h4"
	step="1"
	min="1"
	value="1"
	required
	max="<?php echo esc_attr( $max_at_a_time ); ?>"
	<?php disabled( $must_login ); ?>
/>
