<?php




?>
<div class="tec-tickets__admin-attendees-attendance-totals-row">
	<div class="tec-tickets__admin-attendees-attendance-totals-title" style="font-size:13px;font-weight:700">
		<?php echo esc_html_x( 'Total', 'attendee summary', 'event-tickets' ); ?>
	</div>
	<div class="tec-tickets__admin-attendees-attendance-totals-amt" style="font-size:12px">
		Checked in <span id="total_checkedin"><?php esc_html_e( $total_checked_in ); ?></span> (<span id="percent_checkedin" data-total-attendees="<?php echo esc_attr( $total_attendees ); ?>"><?php esc_html_e( $percent_checked_in ); ?></span>)
	</div>
</div>