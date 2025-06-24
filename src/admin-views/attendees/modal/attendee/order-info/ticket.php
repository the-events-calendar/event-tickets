<?php
/**
 * Attendees modal - Order information > Ticket
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

?>
<div class="tribe-common-g-col tec-tickets__admin-attendees-modal-attendee-info-col">
	<div class="tribe-common-b2--bold"><?php echo esc_html( tribe_get_ticket_label_singular( 'attendee_table_modal' ) ); ?></div>
	<div class="tec-tickets__admin-attendees-modal-attendee-info-value"><?php echo esc_html( $attendee['ticket'] ); ?></div>
</div>
