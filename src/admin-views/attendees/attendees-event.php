<?php
/**
 * Event Attendees screen.
 *
 * @since 5.5.9
 *
 * @var \Tribe__Template          $this      Current template object.
 * @var int                       $event_id  The event/post/page id.
 * @var Tribe__Tickets__Attendees $attendees The Attendees object.
 */

if ( empty( $event_id ) ) {
	return;
}

?>

<?php $this->template( 'attendees/attendees-event/title' ); ?>

<?php $this->template( 'attendees/attendees-event/summary' ); ?>

<?php $this->template( 'attendees/attendees-table' );
