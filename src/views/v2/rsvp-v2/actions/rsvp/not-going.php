<?php
/**
 * RSVP V2: Can't Go Button
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp-v2/actions/rsvp/not-going.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var Tribe__Tickets__Ticket_Object $ticket     The RSVP ticket object.
 * @var int                           $post_id    The event post ID.
 * @var bool                          $must_login Whether the user has to login to RSVP or not.
 */

// Ensure proper context.
if ( empty( $ticket ) || empty( $post_id ) ) {
	return;
}

/**
 * Check if "Can't go" option should be displayed.
 */
$show_not_going = tribe_is_truthy(
	get_post_meta( $ticket->ID, '_tribe_ticket_show_not_going', true )
);

if ( ! $show_not_going ) {
	return;
}

?>
<div class="tribe-tickets__rsvp-v2-actions-rsvp-not-going">
	<button
		class="tribe-common-cta tribe-common-cta--alt tribe-tickets__rsvp-v2-actions-button-not-going"
		type="button"
		data-rsvp-v2-action="not-going"
		<?php tribe_disabled( $must_login ); ?>
	>
		<?php echo esc_html_x( "Can't go", 'Label for the RSVP "can\'t go" version of the not going button', 'event-tickets' ); ?>
	</button>
</div>
