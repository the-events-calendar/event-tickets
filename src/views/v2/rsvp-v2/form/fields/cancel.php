<?php
/**
 * RSVP V2: Cancel Button
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp-v2/form/fields/cancel.php
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
 * @var string                        $going   The RSVP status ('going' or 'not-going').
 */

// Ensure proper context.
if ( empty( $ticket ) || empty( $post_id ) ) {
	return;
}
?>
<button
	class="tribe-common-h7 tribe-tickets__rsvp-v2-form-button tribe-tickets__rsvp-v2-form-button--cancel"
	type="reset"
	data-rsvp-v2-action="cancel"
>
	<?php esc_html_e( 'Cancel', 'event-tickets' ); ?>
</button>
