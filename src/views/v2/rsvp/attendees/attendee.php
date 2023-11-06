<?php
/**
 * Block: RSVP
 * Attendees - Attendee
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp/attendees/title.php
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
 * @since TBD
 *
 * @version TBD
 */

if ( empty( $attendees ) ) {
	return;
}
?>
<div class="tec-tickets__attendees-list-item-attendee-details">
	<?php echo wp_kses_post( $rsvp->name ); ?>
</div>
