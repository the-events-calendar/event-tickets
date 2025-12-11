<?php
/**
 * RSVP V2: Going Button
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp-v2/actions/rsvp/going.php
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
?>

<div class="tribe-tickets__rsvp-v2-actions-rsvp-going">
	<button
		class="tribe-common-c-btn tribe-tickets__rsvp-v2-actions-button-going tribe-common-b1 tribe-common-b2--min-medium"
		type="button"
		data-rsvp-v2-action="going"
		<?php tribe_disabled( $must_login ); ?>
	>
		<?php echo esc_html_x( 'Going', 'Label for the RSVP going button', 'event-tickets' ); ?>
	</button>
</div>
