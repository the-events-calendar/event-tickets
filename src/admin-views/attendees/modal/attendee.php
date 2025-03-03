<?php
/**
 * Attendees modal.
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
<div class="tribe-common-b2 tribe-common-g-row tribe-common-g-row--gutters tec-tickets__admin-attendees-modal-wrapper">

	<div class="tribe-common-g-col tec-tickets__admin-attendees-modal-content">
		<?php $this->template( 'attendees/modal/attendee/check-in' ); ?>

		<?php $this->template( 'attendees/modal/attendee/attendee-info' ); ?>

		<?php $this->template( 'attendees/modal/attendee/order-info' ); ?>
	</div>

	<div class="tribe-common-g-col tec-tickets__admin-attendees-modal-sidebar">
		<?php $this->template( 'attendees/modal/attendee/gravatar' ); ?>
		<?php $this->template( 'attendees/modal/attendee/qr-image' ); ?>
	</div>

</div>
