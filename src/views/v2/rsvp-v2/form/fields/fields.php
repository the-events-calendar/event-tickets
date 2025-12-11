<?php
/**
 * RSVP V2: Fields Container
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp-v2/form/fields/fields.php
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

$this->template( 'v2/rsvp-v2/form/fields/name', [ 'ticket' => $ticket, 'post_id' => $post_id, 'going' => $going ] );
$this->template( 'v2/rsvp-v2/form/fields/email', [ 'ticket' => $ticket, 'post_id' => $post_id, 'going' => $going ] );
$this->template( 'v2/rsvp-v2/form/fields/quantity', [ 'ticket' => $ticket, 'post_id' => $post_id, 'going' => $going ] );
