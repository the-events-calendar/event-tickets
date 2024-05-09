<?php
/**
 * Attendees modal - Check-in details.
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

if ( empty( $attendee['product_id'] ) ) {
	return;
}

$provider = tribe_tickets_get_ticket_provider( $attendee['product_id'] );
$details  = get_post_meta( $attendee_id, $provider->checkin_key . '_details', true );

$classes = [
	'tec-tickets__admin-attendees-modal-checkin-info',
	'tec-tickets__admin-attendees-modal-checkin-info--checked-in' => ! empty( $details ),
];
?>

<div <?php tribe_classes( $classes ); ?>>
	<?php $this->template( 'attendees/modal/attendee/check-in/checked-in', [ 'details' => $details ] ); ?>
	<?php $this->template( 'attendees/modal/attendee/check-in/not-checked-in', [ 'details' => $details ] ); ?>
</div>
