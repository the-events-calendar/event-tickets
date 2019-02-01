<?php
/**
 * This template renders the event summary description
 * for the registration page
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/registration/summary/description.php
 *
 * @version 4.9
 *
 */
?>
<?php if ( class_exists( 'Tribe__Events__Main' ) ) : ?>
<div class="tribe-block__tickets__registration__description">
	<?php echo tribe_events_event_schedule_details( $event_id ); ?>
</div>
<?php endif;
