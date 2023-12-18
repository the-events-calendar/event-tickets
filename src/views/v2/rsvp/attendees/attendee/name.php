<?php
/**
 * Block: RSVP
 * Attendees - Attendee Name
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp/attendees/attendee/name.php
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
 * @since 5.7.1
 *
 * @version 5.7.1
 */

if ( empty( $attendees ) || empty( $attendee_id ) ) {
	return;
}

$attendee_name = get_post_meta( $attendee_id, tribe( Tribe__Tickets__RSVP::class )->full_name, true );
if ( empty( $attendee_name ) ) {
	return;
}

?>
<div class="tec-tickets__attendees-list-item-attendee-details-name tribe-common-b1--bold">
	<?php echo esc_html( $attendee_name ); ?>
</div>
