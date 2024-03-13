<?php
/**
 * Attendees modal - Check-in details > Checked-in.
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

if ( empty( $attendee['product_id'] ) || empty( $details ) ) {
	return;
}

$date_format    = tribe_get_date_format( true );
$checkin_date   = Tribe__Date_Utils::build_date_object( $details['date'] )->format_i18n( $date_format );
$checkin_source = 'site' === $details['source'] ? __( 'Web', 'event-tickets' ) : __( 'Mobile app', 'event-tickets' );

echo '<span class="tec-tickets__admin-attendees-modal-checkin-info-icon dashicons dashicons-yes-alt"></span> ';
printf(
	/* translators: %1$s: check-in date. %2$s: source of check-in. */
	esc_html__( 'Checked in on %1$s via %2$s', 'event-tickets' ),
	esc_html( $checkin_date ),
	esc_html( $checkin_source )
);
