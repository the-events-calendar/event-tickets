<?php
/**
 * Block: RSVP
 * Details Availability - Remaining
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp/details/availability/remaining.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link  {INSERT_ARTICLE_LINK_HERE}
 *
 * @since TBD
 *
 * @version TBD
 */

/** @var Tribe__Settings_Manager $settings_manager */
$settings_manager = tribe( 'settings.manager' );

$threshold         = $settings_manager::get_option( 'ticket-display-tickets-left-threshold', 0 );
$remaining_tickets = $rsvp->remaining();

/**
 * Overwrites the threshold to display "# tickets left".
 *
 * @param int   $threshold Stock threshold to trigger display of "# tickets left"
 * @param array $data      Ticket data.
 * @param int   $event_id  Event ID.
 *
 * @since 4.11.1
 */
$threshold = absint( apply_filters( 'tribe_display_rsvp_block_tickets_left_threshold', $threshold, tribe_events_get_ticket_event( $rsvp ) ) );

if ( 0 !== $threshold && $remaining_tickets > $threshold ) {
	return;
}

?>
<span class="tribe-tickets__rsvp-availability-quantity tribe-common-b2--bold"><?php echo esc_html( $remaining_tickets ); ?> </span>
<?php esc_html_e( 'remaining', 'event-tickets' ); ?>,
