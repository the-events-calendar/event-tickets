<?php
/**
 * This template renders the event summary description
 * for the registration page
 *
 * @version TBD
 *
 */
?>
<?php if ( class_exists( 'Tribe__Events__Main' ) ) : ?>
<div class="tribe-block__tickets__registration__description">
	<?php echo tribe_events_event_schedule_details( $event_id ); ?>
</div>
<?php endif; ?>