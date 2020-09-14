<?php
/**
 * Block: Tickets
 * Form Opt-Out
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/tickets/opt-out/hidden.php
 *
 * See more documentation about our views templating system.
 *
 * @link http://m.tri.be/1amp
 *
 * @since TBD
 *
 * @version 4.11.3
 *
 */

if ( empty( $is_modal ) ) {
	return;
}

/**
 * Use this filter to hide the Attendees List Optout
 *
 * @since 4.9
 *
 * @param bool
 */
$hide_attendee_list_optout = apply_filters( 'tribe_tickets_plus_hide_attendees_list_optout', false );

if ( $hide_attendee_list_optout ) {
	// Force optout.
	?>
	<input name="attendee[optout]" value="1" type="hidden" />
	<?php
	return;
}
?>

<input id="tribe-tickets-attendees-list-optout-<?php echo esc_attr( $ticket->ID ); ?>-modal" class="tribe-tickets__item__quantity" name="attendee[optout]" value="1" type="hidden" />
