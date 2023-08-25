<?php
/**
 * @var string $total_sold_label
 * @var string $total_complete_label
 * @var string $total_cancelled_label
 * @var string $total_sold
 * @var string $total_complete
 * @var string $total_cancelled
 * @var string $total_sold_tooltip
 * @var string $total_completed_tooltip
 * @var string $total_cancelled_tooltip
 */
?>
<div class="tec-tickets__admin-attendees-attendance-type">
	<div class="tec-tickets__admin-attendees-attendance-type-heading">
		<div class="tec-tickets__admin-attendees-attendance-type-heading-label">
			<?php esc_html_e( $total_type_label ); ?>
		</div>
		<div class="tec-tickets__admin-attendees-attendance-type-heading-border"></div>
		<div class="tec-tickets__admin-attendees-attendance-type-heading-total">
			<span class="tec-tickets__admin-attendees-attendance-type-heading-total-label">
				<?php echo esc_html__( 'Total', 'event-tickets' ); ?>
			</span>
			<span class="tec-tickets__admin-attendees-attendance-type-heading-total-amt">
				<?php esc_html_e( $total_sold ); ?>
			</span>
		</div>
	</div>
	<div class="tec-tickets__admin-attendees-attendance-type-complete">
		<div class="tec-tickets__admin-attendees-attendance-type-complete-label">
			<?php esc_html_e( $total_complete_label ); ?>
		</div>
		<div class="tec-tickets__admin-attendees-attendance-type-complete-amt">
			<?php esc_html_e( $total_complete ); ?>
		</div>
	</div>
	<div class="tec-tickets__admin-attendees-attendance-type-cancelled">
		<div class="tec-tickets__admin-attendees-attendance-type-cancelled-label">
			<?php esc_html_e( $total_cancelled_label ); ?>
		</div>
		<div class="tec-tickets__admin-attendees-attendance-type-cancelled-amt">
			<?php esc_html_e( $total_cancelled ); ?>
		</div>
	</div>
</div>
