<?php
/**
 * Attendees modal - Attendee information
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

if ( empty( $attendee_id ) ) {
	return;
}

?>
<div class="tec-tickets__admin-attendees-modal-section tec-tickets__admin-attendees-modal-section--attendee-information">

	<div class="tribe-common-g-row tribe-common-g-row--gutters tec-tickets__admin-attendees-modal-attendee-info-row">
		<?php $this->template( 'attendees/modal/attendee/attendee-info/id' ); ?>
		<?php $this->template( 'attendees/modal/attendee/attendee-info/security-code' ); ?>
	</div>
	<div class="tribe-common-g-row tribe-common-g-row--gutters tec-tickets__admin-attendees-modal-attendee-info-row">
		<?php $this->template( 'attendees/modal/attendee/attendee-info/email' ); ?>
		<?php $this->template( 'attendees/modal/attendee/attendee-info/rsvp-going' ); ?>
	</div>
</div>
