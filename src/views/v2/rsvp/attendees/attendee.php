<?php
/**
 * Block: RSVP
 * Attendees - Attendee
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp/attendees/attendee.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @var Tribe__Tickets__Ticket_Object $rsvp The rsvp ticket object.
 * @var string|null $step The step the views are on.
 * @var array $attendees List of attendees for the given order.
 * @var int $attendee_id The attendee ID.
 *
 * @since 5.7.1 - Add attendee name and rsvp name templates.
 * @since 5.7.0
 *
 * @version 5.7.1
 */

if ( empty( $attendees ) ) {
	return;
}

?>
<div class="tec-tickets__attendees-list-item-attendee-details tribe-common-b1">
	<?php $this->template( 'v2/rsvp/attendees/attendee/name' ); ?>
	<?php $this->template( 'v2/rsvp/attendees/attendee/rsvp' ); ?>
</div>
