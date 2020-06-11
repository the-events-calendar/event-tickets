<?php
/**
 * Block: RSVP
 * Details Availability - Unlimited
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp/details/availability/unlimited.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link  {INSERT_ARTICLE_LINK_HERE}
 *
 * @since TBD
 *
 * @version TBD
 */

$is_unlimited = $this->get( 'is_unlimited' );

/**
 * Allows hiding of "unlimited" to be toggled on/off conditionally.
 *
 * @param int   $show_unlimited allow showing of "unlimited".
 *
 * @since 4.11.1
 */
$show_unlimited = apply_filters( 'tribe_rsvp_block_show_unlimited_availability', false, $is_unlimited );

if ( empty( $show_unlimited ) ) {
	return;
}

/** @var Tribe__Tickets__Tickets_Handler $handler */
$handler = tribe( 'tickets.handler' );

?>
<span class="tribe-tickets__rsvp-availability-unlimited">
	<?php echo esc_html( $handler->unlimited_term ); ?>
</span>
