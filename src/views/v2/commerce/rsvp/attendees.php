<?php
/**
 * Block: RSVP
 * Attendees
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp/attendees.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @var Tribe__Tickets__Ticket_Object $rsvp The rsvp ticket object.
 * @var string|null $step The step the views are on.
 * @var array $attendees List of attendees IDs confirmed for the RSVP.
 *
 * @since 5.7.0
 *
 * @version 5.7.0
 */

if ( empty( $attendees ) ) {
	return;
}
?>
<div class="tribe-tickets__rsvp-attendees-wrapper tribe-common-g-row">
	<div class="tec-tickets__attendees-list-wrapper tribe-tickets__rsvp-attendees tribe-common-b1 tribe-common-g-col">
		<?php $this->template( 'v2/rsvp/attendees/title' ); ?>

		<div class="tec-tickets__attendees-list">
			<?php foreach ( $attendees as $key => $attendee_id ) : ?>
				<div class="tec-tickets__attendees-list-item">
					<?php $this->template( 'v2/rsvp/attendees/attendee', [ 'attendee_id' => $attendee_id ] ); ?>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</div>
