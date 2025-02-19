<?php
/**
 * Attendees modal - Order information > Order ID
 *
 * @since 5.10.0
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

if ( empty( $attendee['order_id'] ) ) {
	return;
}

$order_url = ! empty( $attendee['order_id_url'] ) ? $attendee['order_id_url'] : '';
$order_id  = ! empty( $order_url ) ? '<a href="' . esc_url( $order_url ) . '" target="_blank">#' . esc_html( $attendee['order_id'] ) . '</a>' : '#' . esc_html( $attendee['order_id'] );
?>
<div class="tribe-common-g-col tec-tickets__admin-attendees-modal-attendee-info-col">
	<div class="tribe-common-b2--bold"><?php esc_html_e( 'Order ID', 'event-tickets' ); ?></div>
	<div class="tec-tickets__admin-attendees-modal-attendee-info-value"><?php echo $order_id; // phpcs:ignore ?></div>
</div>
