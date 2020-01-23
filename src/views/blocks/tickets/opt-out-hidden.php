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
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since 4.11.0
 * @version 4.11.0
 *
 */
$ticket   = $this->get( 'ticket' );

/**
 * Use this filter to hide the Attendees List Optout
 *
 * @since 4.9
 *
 * @param bool
 */
$hide_attendee_list_optout = apply_filters( 'tribe_tickets_plus_hide_attendees_list_optout', false );
if (
	$hide_attendee_list_optout
	|| ! class_exists( 'Tribe__Tickets_Plus__Attendees_List' )
	|| Tribe__Tickets_Plus__Attendees_List::is_hidden_on( $this->get( 'post_id' ) )
) {
	return;
}
?>
<input id="tribe-tickets-attendees-list-optout-<?php echo esc_attr( $ticket->ID ); ?>-modal" class="tribe-tickets__item__quantity" name="attendee[optout]" type="hidden" />
