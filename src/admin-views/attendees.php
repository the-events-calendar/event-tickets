<?php
/**
 * Attendees template.
 *
 * @var \Tribe__Template          $this      Current template object.
 * @var int                       $event_id  The event/post/page id.
 * @var Tribe__Tickets__Attendees $attendees The Attendees object.
 */

$wrapper_classes = [
	'wrap',
	'tribe-report-page',
	'tec-tickets__admin-attendees',
	'tec-tickets__admin-attendees--event'     => ! empty( $event_id ),
	'tec-tickets__admin-attendees--front-end' => empty( is_admin() ),
];
?>
<div <?php tribe_classes( $wrapper_classes ); ?>>
	<?php
	$this->template( 'attendees/attendees' );
	$this->template( 'attendees/attendees-event' );
	?>
</div>
