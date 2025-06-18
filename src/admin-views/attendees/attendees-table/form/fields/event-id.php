<?php
/**
 * Attendees table form fields > Event ID.
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
<input
	type="hidden"
	name="<?php echo esc_attr( is_admin() ? 'event_id' : 'tribe[event_id]' ); ?>"
	id="event_id"
	value="<?php echo esc_attr( $event_id ); ?>"
/>
