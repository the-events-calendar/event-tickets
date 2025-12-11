<?php
/**
 * RSVP V2: Unlimited Message
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp-v2/details/availability/unlimited.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var Tribe__Tickets__Ticket_Object $ticket        The RSVP ticket object.
 * @var int                           $post_id       The event post ID.
 * @var bool                          $is_unlimited  Whether ticket capacity is unlimited.
 * @var int|false                     $days_to_rsvp  Days until RSVP closes, or false.
 */

// Ensure proper context.
if ( empty( $ticket ) || empty( $post_id ) ) {
	return;
}

$is_unlimited = $this->get( 'is_unlimited' );

if ( empty( tribe( 'tickets.editor.blocks.rsvp' )->show_unlimited( $is_unlimited ) ) ) {
	return;
}

/** @var Tribe__Tickets__Tickets_Handler $handler */
$handler = tribe( 'tickets.handler' );

?>
<span class="tribe-tickets__rsvp-v2-availability-unlimited">
	<?php echo esc_html( $handler->unlimited_term . ( false !== $days_to_rsvp ? ',' : '' ) ); ?>
</span>
