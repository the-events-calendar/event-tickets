<?php
/**
 * Block: RSVP ARi
 * Form meta fields.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp/ari/form/fields/meta.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @since5.0.0
 *
 * @version5.0.0
 */

/**
 * Filter to check if the RSVP has meta.
 *
 * @since5.0.0
 *
 * @param Tribe__Tickets__Ticket_Object $rsvp The rsvp ticket object.
 *
 * @return bool
 */
$has_meta = apply_filters( 'tribe_tickets_rsvp_has_meta', $rsvp, false );

if ( empty( $has_meta ) ) {
	return;
}

/**
 * Allows injection of meta fields in the RSVP ARI form.
 *
 * @since5.0.0
 *
 * @see  Tribe__Template\do_entry_point()
 * @link https://docs.theeventscalendar.com/reference/classes/tribe__template/do_entry_point/
 */
$this->do_entry_point( 'rsvp_attendee_fields' );
