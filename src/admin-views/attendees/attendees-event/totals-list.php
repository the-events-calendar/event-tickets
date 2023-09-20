<?php
/**
 * Attendance ticket totals template
 *
 * @since 5.6.5
 *
 * @var string $total_sold_label        The label for the total sold tickets.
 * @var string $total_complete_label    The label for the total completed tickets.
 * @var string $total_cancelled_label   The label for the total cancelled tickets.
 * @var string $total_sold              The total sold tickets.
 * @var string $total_complete          The total completed tickets.
 * @var string $total_cancelled         The total cancelled tickets.
 * @var string $total_sold_tooltip      The tooltip for the total sold tickets.
 * @var string $total_completed_tooltip The tooltip for the total completed tickets.
 * @var string $total_cancelled_tooltip The tooltip for the total cancelled tickets.
 */
?>
<div class="tec-tickets__admin-attendees-attendance-type">
	<div class="tec-tickets__admin-attendees-attendance-type-heading">
		<div class="tec-tickets__admin-attendees-attendance-type-heading-label">
			<?php echo esc_html( $total_type_label ); ?>
		</div>
		<div class="tec-tickets__admin-attendees-attendance-type-heading-border"></div>
		<div class="tec-tickets__admin-attendees-attendance-type-heading-total">
			<span class="tec-tickets__admin-attendees-attendance-type-heading-total-label">
				<?php echo esc_html__( 'Total', 'event-tickets' ); ?>
			</span>
			<span class="tec-tickets__admin-attendees-attendance-type-heading-total-amount">
				<?php echo esc_html( $total_sold ); ?>
			</span>
		</div>
	</div>
	<div class="tec-tickets__admin-attendees-attendance-type-complete">
		<div class="tec-tickets__admin-attendees-attendance-type-complete-label">
			<?php echo esc_html( $total_complete_label ); ?>
		</div>
		<div class="tec-tickets__admin-attendees-attendance-type-complete-amount">
			<?php echo esc_html( $total_complete ); ?>
		</div>
	</div>
	<div class="tec-tickets__admin-attendees-attendance-type-cancelled">
		<div class="tec-tickets__admin-attendees-attendance-type-cancelled-label">
			<?php echo esc_html( $total_cancelled_label ); ?>
		</div>
		<div class="tec-tickets__admin-attendees-attendance-type-cancelled-amount">
			<?php echo esc_html( $total_cancelled ); ?>
		</div>
	</div>
</div>
