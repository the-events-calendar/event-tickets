<?php
/**
 * The attendance of the next date of a recurring event, above the attendance totals of every date on its Attendees page.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/admin-views/recurring-tickets/attendees/next-date.php
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var string $title           The title, naming the next date; empty when none is to come.
 * @var int    $total           The tickets issued on the date.
 * @var int    $complete        The tickets of completed orders on the date.
 * @var int    $cancelled       The tickets cancelled on the date.
 * @var int    $checked_in      The attendees of the date checked in.
 * @var string $percent         The share of the date's attendees checked in.
 * @var string $all_dates_title The title of the attendance totals of every date, below.
 */

defined( 'ABSPATH' ) || exit;
?>
<?php if ( $title ) : ?>
	<h4 class="tec-tickets__admin-attendees-attendance-date-title"><?php echo esc_html( $title ); ?></h4>
	<div class="tec-tickets__admin-attendees-attendance-type">
		<div class="tec-tickets__admin-attendees-attendance-type-heading">
			<div class="tec-tickets__admin-attendees-attendance-type-heading-label">
				<?php echo esc_html( tribe_get_ticket_label_plural( 'total_type_label' ) ); ?>
			</div>
			<div class="tec-tickets__admin-attendees-attendance-type-heading-border"></div>
			<div class="tec-tickets__admin-attendees-attendance-type-heading-total">
				<span class="tec-tickets__admin-attendees-attendance-type-heading-total-label"><?php echo esc_html__( 'Total', 'event-tickets' ); ?></span>
				<span class="tec-tickets__admin-attendees-attendance-type-heading-total-amount"><?php echo esc_html( $total ); ?></span>
			</div>
		</div>
		<div class="tec-tickets__admin-attendees-attendance-type-complete">
			<div class="tec-tickets__admin-attendees-attendance-type-complete-label"><?php echo esc_html_x( 'Complete:', 'attendee summary', 'event-tickets' ); ?></div>
			<div class="tec-tickets__admin-attendees-attendance-type-complete-amount"><?php echo esc_html( $complete ); ?></div>
		</div>
		<div class="tec-tickets__admin-attendees-attendance-type-cancelled">
			<div class="tec-tickets__admin-attendees-attendance-type-cancelled-label"><?php echo esc_html_x( 'Cancelled:', 'attendee summary', 'event-tickets' ); ?></div>
			<div class="tec-tickets__admin-attendees-attendance-type-cancelled-amount"><?php echo esc_html( $cancelled ); ?></div>
		</div>
	</div>
	<div class="tec-tickets__admin-attendees-attendance-totals-row">
		<div class="tec-tickets__admin-attendees-attendance-totals-title"><?php echo esc_html_x( 'Total', 'attendee summary', 'event-tickets' ); ?></div>
		<div class="tec-tickets__admin-attendees-attendance-totals-amount">
			<?php
			// translators: %1$d is the number of attendees checked in, %2$s the share of the date's attendees, e.g. "50%".
			echo esc_html( sprintf( __( 'Checked in %1$d (%2$s)', 'event-tickets' ), $checked_in, $percent ) );
			?>
		</div>
	</div>
<?php endif; ?>
<h4 class="tec-tickets__admin-attendees-attendance-date-title"><?php echo esc_html( $all_dates_title ); ?></h4>
