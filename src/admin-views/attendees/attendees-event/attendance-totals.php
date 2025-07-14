<?php
/**
 * Event Attendance Totals template.
 *
 * @since 5.6.5
 *
 * @var \Tribe__Template          $this               Current template object.
 * @var int                       $event_id           The event/post/page id.
 * @var Tribe__Tickets__Attendees $attendees          The Attendees object.
 * @var int                       $total_checked_in   The total number of attendees checked in.
 * @var int                       $total_attendees    The total number of attendees.
 * @var string                    $percent_checked_in The percentage of attendees checked in.
 */

?>
<div class="tec-tickets__admin-attendees-attendance-totals-row">
	<div class="tec-tickets__admin-attendees-attendance-totals-title">
		<?php echo esc_html_x( 'Total', 'attendee summary', 'event-tickets' ); ?>
	</div>
	<div class="tec-tickets__admin-attendees-attendance-totals-amount">
		<?php esc_html_e( 'Checked in', 'event-tickets' ); ?> <span id="total_checkedin"><?php echo esc_html( $total_checked_in ); ?></span> (<span id="percent_checkedin" data-total-attendees="<?php echo esc_attr( $total_attendees ); ?>"><?php echo esc_html( $percent_checked_in ); ?></span>)
	</div>
</div>
