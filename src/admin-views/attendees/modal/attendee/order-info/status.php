<?php
/**
 * Attendees modal - Order information > Status
 *
 * @since  TBD
 *
 * @var Tribe_Template $this           Current template object.
 * @var \WP_Post       $attendee       The attendee object.
 * @var int            $attendee_id    The attendee ID.
 * @var string         $attendee_name  The attendee name.
 * @var string         $attendee_email The attendee email.
 * @var int            $post_id        The ID of the associated post.
 * @var int            $ticket_id      The ID of the associated ticket.
 * @var bool           $qr_enabled     True if QR codes are enabled for the site.
 */

switch ( $attendee['order_status'] ) {
	case 'cancelled':
	case 'failed':
	case tribe( \TEC\Tickets\Commerce\Status\Not_Completed::class )->get_name():
	case tribe( \TEC\Tickets\Commerce\Status\Denied::class )->get_name():
		$icon = '<span class="dashicons dashicons-warning"></span> ';
		break;
	case 'on-hold':
		$icon = '<span class="dashicons dashicons-flag"></span> ';
		break;
	case 'refunded':
	case tribe( \TEC\Tickets\Commerce\Status\Refunded::class )->get_name():
		$icon = '<span class="dashicons dashicons-undo"></span> ';
		break;
	case tribe( \TEC\Tickets\Commerce\Status\Pending::class )->get_name():
		$icon = '<span class="dashicons dashicons-clock"></span> ';
		break;
	default:
		$icon = '';
		break;
}
?>
<div class="tribe-common-g-col tec-tickets__admin-attendees-modal-attendee-info-col">
	<div class="tribe-common-b2--bold"><?php esc_html_e( 'Order status', 'event-tickets' ); ?></div>
	<div class="tec-tickets__admin-attendees-modal-attendee-info-value">
		<?php echo $icon . esc_html( ucwords( $attendee['order_status'] ) ); // phpcs:ignore ?>
	</div>
</div>
