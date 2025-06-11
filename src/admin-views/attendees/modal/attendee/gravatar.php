<?php
/**
 * Attendees modal - Gravatar
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

// Only show if there are no QR codes enabled.
if ( ! empty( $qr_enabled ) ) {
	return;
}
?>
<div class="tec-tickets__admin-attendees-modal-gravatar">
	<?php echo get_avatar( $attendee_email, 96 ); ?>
</div>
