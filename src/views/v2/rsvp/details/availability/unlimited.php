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

if ( empty( tribe( 'tickets.editor.blocks.rsvp' )->show_unlimited( $is_unlimited ) ) ) {
	return;
}

/** @var Tribe__Tickets__Tickets_Handler $handler */
$handler = tribe( 'tickets.handler' );

?>
<span class="tribe-tickets__rsvp-availability-unlimited">
	<?php echo esc_html( $handler->unlimited_term ); ?>
</span>
