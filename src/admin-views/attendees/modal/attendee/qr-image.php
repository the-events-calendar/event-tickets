<?php
/**
 * Attendees modal - QR Image
 *
 * @since  5.10.0
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

if ( empty( $qr_enabled ) ) {
	return;
}

use TEC\Tickets\QR\Connector;
$qr_image = tribe( Connector::class )->get_image_url_from_ticket_data( $attendee );
?>
<div class="tec-tickets__admin-attendees-modal-qr-image">
	<img
		src="<?php echo esc_url( $qr_image ); ?>"
		alt="<?php esc_attr_e( 'QR Code for attendee', 'event-tickets' ); ?>"
	/>
</div>
