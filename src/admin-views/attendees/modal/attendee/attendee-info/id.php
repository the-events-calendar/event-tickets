<?php
/**
 * Attendees modal - Attendee information > ID.
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

$unique_id = tribe( 'tickets.admin.attendees_table' )->get_attendee_id( $attendee );
// Purposefully not forcing strict check here.
// phpcs:ignore
if ( $attendee_id != $unique_id ) {
	$attendee_id = sprintf( '#%d - %s', $attendee_id, $unique_id );
} else {
	$attendee_id = sprintf( '#%s', $attendee_id );
}
?>
<div class="tribe-common-g-col tec-tickets__admin-attendees-modal-attendee-info-col">
	<div class="tribe-common-b2--bold"><?php esc_html_e( 'Attendee ID', 'event-tickets' ); ?></div>
	<div class="tec-tickets__admin-attendees-modal-attendee-info-value"><?php echo esc_html( $attendee_id ); ?></div>
</div>
