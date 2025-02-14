<?php
/**
 * Attendees table form fields > Post type.
 *
 * @since 5.5.9
 *
 * @var \Tribe__Template          $this      Current template object.
 * @var int                       $event_id  The event/post/page id.
 * @var Tribe__Tickets__Attendees $attendees The Attendees object.
 */

$event = $attendees->attendees_table->event;

if ( empty( $event->post_type ) ) {
	return;
}

?>
<input
	type="hidden"
	name="<?php echo esc_attr( is_admin() ? 'post_type' : 'tribe[post_type]' ); ?>"
	value="<?php echo esc_attr( $event->post_type ); ?>"
/>
