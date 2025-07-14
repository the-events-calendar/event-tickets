<?php
/**
 * Event Attendance Overview template.
 *
 * @since 5.6.5
 *
 * @var \Tribe__Template          $this      Current template object.
 * @var int                       $event_id  The event/post/page id.
 * @var Tribe__Tickets__Attendees $attendees The Attendees object.
 */

?>
<div class="welcome-panel-column welcome-panel-last alternate">
	<h3><?php echo esc_html__( 'Attendance Overview', 'event-tickets' ); ?></h3>
	<?php
		/**
		 * Fires before the main body of attendee totals are rendered.
		 *
		 * @param int $event_id
		 */
		do_action( 'tribe_events_tickets_attendees_totals_top', $event_id );
	?>
	<div class="tec-tickets__admin-attendees-attendance-type-list">
		<?php
			/**
			 * Trigger for the creation of attendee totals within the attendee
			 * screen summary box.
			 *
			 * @param int $event_id
			 */
			do_action( 'tribe_tickets_attendees_totals', $event_id );
		?>
	</div>
	<?php
		/**
		 * Fires after the main body of attendee totals are rendered.
		 *
		 * @param int $event_id
		 */
		do_action( 'tribe_events_tickets_attendees_totals_bottom', $event_id );
	?>
</div>
