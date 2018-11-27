<?php
/**
 * Block: RSVP
 * Form Opt-Out
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/blocks/rsvp/form/opt-out.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTCILE_LINK_HERE}
 *
 * @version TBD
 *
 */
/**
 * Use this filter to hide the Attendees List Optout
 *
 * @since TBD
 *
 * @param bool
 */
$hide_attendee_list_optout = apply_filters( 'tribe_tickets_plus_hide_attendees_list_optout', false );
if ( $hide_attendee_list_optout
	 && ! class_exists( 'Tribe__Tickets_Plus__Attendees_List' )
	 && Tribe__Tickets_Plus__Attendees_List::is_hidden_on( $this->get( 'post_id' ) )
) {
	return;
}
?>
<label for="tribe-tickets-attendees-list-optout">
	<input
		type="checkbox"
		name="attendee[optout]"
		id="tribe-tickets-attendees-list-optout"
	>
	<span class="tribe-tickets-meta-option-label">
		<?php esc_html_e( "Don't show my information on public attendee lists", 'events-gutenberg' ); ?>
	</span>
</label>