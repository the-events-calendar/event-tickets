<?php
/**
 * RSVP V2: Opt-in Tooltip
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp-v2/actions/success/tooltip.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var Tribe__Tickets__Ticket_Object $ticket  The RSVP ticket object.
 * @var int                           $post_id The event post ID.
 */

// Ensure proper context.
if ( empty( $ticket ) || empty( $post_id ) ) {
	return;
}
?>
<div class="tribe-common-a11y-hidden">
	<div
		class="tribe-common-b3"
		id="tribe-tickets-tooltip-content-v2-<?php echo esc_attr( $ticket->ID ); ?>"
		role="tooltip"
	>
		<?php esc_html_e( 'Enabling this allows your gravatar and name to be present for other attendees to see.', 'event-tickets' ); ?>
	</div>
</div>
