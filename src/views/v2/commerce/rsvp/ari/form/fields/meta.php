<?php
/**
 * This template renders the RSVP Attendee Registration form fields.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp/ari/form/fields/meta.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var Tribe__Tickets__Ticket_Object $rsvp The RSVP ticket object.
 */

if ( ! $rsvp->has_meta_enabled() ) {
	return;
}

/**
 * Allows injection of meta fields in the RSVP ARI form.
 *
 * @since TBD
 *
 * @see Tribe__Template\do_entry_point()
 * @link https://docs.theeventscalendar.com/reference/classes/tribe__template/do_entry_point/
 */
$this->do_entry_point( 'rsvp_attendee_fields' );
