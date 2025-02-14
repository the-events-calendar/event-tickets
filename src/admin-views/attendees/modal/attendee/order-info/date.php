<?php
/**
 * Attendees modal - Order information > date
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

$date_format   = tribe_get_date_format( true );
$purchase_time = get_post_time( Tribe__Date_Utils::DBDATETIMEFORMAT, false, $attendee['order_id'] );

if ( empty( $purchase_time ) ) {
	return;
}
?>
<div class="tribe-common-g-col tec-tickets__admin-attendees-modal-attendee-info-col">
	<div class="tribe-common-b2--bold"><?php esc_html_e( 'Date', 'event-tickets' ); ?></div>
	<div class="tec-tickets__admin-attendees-modal-attendee-info-value">
		<?php echo esc_html( Tribe__Date_Utils::build_date_object( $purchase_time )->format_i18n( $date_format ) ); ?>
	</div>
</div>
