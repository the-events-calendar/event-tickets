<?php
/**
 * Block: Tickets
 * Extra column, available Quantity
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/tickets/extra-available-quantity.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link    {INSERT_ARTICLE_LINK_HERE}
 *
 * @since   4.9.3
 * @since   TBD Corrected amount of available/remaining tickets.
 *
 * @version TBD
 *
 * @var Tribe__Tickets__Editor__Template $this
 * @var Tribe__Tickets__Ticket_Object    $ticket    // From the 'extra-available' template including this template
 * @var int                              $available // From the 'extra-available' template including this template
 */

if (
	empty( $ticket->ID )
	|| ! isset( $available )
	|| 0 === $available
	|| -1 > $available
) {
	return;
}
?>
<span class="tribe-tickets__item__extra__available__quantity"><?php echo esc_html( $available ); ?></span>
<?php esc_html_e( 'available', 'event-tickets' );