<?php
/**
 * This template renders the event summary description
 * for the registration page
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/attendee-registration/event/summary/description.php
 *
 * See more documentation about our views templating system.
 *
 * @link http://m.tri.be/1amp
 *
 * @since TBD
 * @version TBD
 *
 * @var int $post_id The event/post ID.
 */

// Bail if The Events Calendar is not active.
if ( ! class_exists( 'Tribe__Events__Main' ) ) {
	return;
}

?>
<div class="tribe-common-b2 tribe-tickets__registration__description">
	<?php echo tribe_events_event_schedule_details( $post_id ); ?>
</div>
