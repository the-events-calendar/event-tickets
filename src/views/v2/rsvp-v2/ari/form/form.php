<?php
/**
 * RSVP V2: ARI Form
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp-v2/ari/form/form.php
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

/**
 * Placeholder for ARI form template.
 * This will be extended by Event Tickets Plus for attendee registration.
 */
?>
<div class="tribe-tickets__rsvp-v2-ar-form">
	<?php
	/**
	 * Allows additional content to be injected into the ARI form.
	 *
	 * @since TBD
	 *
	 * @param Tribe__Tickets__Ticket_Object $ticket  The RSVP ticket object.
	 * @param int                           $post_id The event post ID.
	 */
	do_action( 'tec_tickets_rsvp_v2_ari_form', $ticket, $post_id );
	?>
</div>
