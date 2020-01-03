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
 * @since 4.9
 * @since 4.11.0 Updated the optout markup and classes used.
 * @version 4.11.0
 *
 */
$modal    = $this->get( 'is_modal' );
$ticket   = $this->get( 'ticket' );
/**
 * Use this filter to hide the Attendees List Optout
 *
 * @since 4.9
 *
 * @param bool
 */
$hide_attendee_list_optout = apply_filters( 'tribe_tickets_plus_hide_attendees_list_optout', $modal );
if ( $hide_attendee_list_optout
	 && ! class_exists( 'Tribe__Tickets_Plus__Attendees_List' )
	 && Tribe__Tickets_Plus__Attendees_List::is_hidden_on( $this->get( 'post_id' ) )
) {
	return;
}
$field_id = [
	'tribe-tickets-attendees-list-optout',
	$ticket->ID
];

$field_id = implode( '-', $field_id );
?>
<div class="tribe-common-form-control-checkbox tribe-tickets-attendees-list-optout--wrapper">
	<label
		class="tribe-common-form-control-checkbox__label"
		for="<?php echo esc_attr( $field_id ); ?>"
	>
		<input
			class="tribe-common-form-control-checkbox__input tribe-tickets__item__optout"
			id="<?php echo esc_attr( $field_id ); ?>"
			name="attendee[optout]"
			type="checkbox"
		/><?php esc_html_e( "Don't show me on public attendee lists.", 'event-tickets' ); ?></label>
</div>
