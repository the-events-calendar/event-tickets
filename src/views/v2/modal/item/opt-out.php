<?php
/**
 * Block: Tickets
 * Form Opt-Out
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/modal/item/opt-out.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var bool $is_modal True if it's in modal context.
 */

// Bail if it's not in modal context.
if ( empty( $is_modal ) ) {
	return;
}

/**
 * Use this filter to hide the Attendees List Opt-out.
 *
 * @since 4.9
 *
 * @param bool
 */
$hide_attendee_list_optout = apply_filters( 'tribe_tickets_plus_hide_attendees_list_optout', false );

if ( $hide_attendee_list_optout ) {
	// Force opt-out.
	?>
	<input
		name="attendee[optout]"
		value="1"
		type="hidden"
	/>
	<?php
	return;
}
?>

<input
	id="tribe-tickets-attendees-list-optout-<?php echo esc_attr( $ticket->ID ); ?>-modal"
	class="tribe-tickets__item__quantity"
	name="attendee[optout]"
	value="1"
	type="hidden"
/>
