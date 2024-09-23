<?php
/**
 * Attendees modal - Purchase info.
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

// Early bail if it's an RSVP.
if ( ! empty( $attendee['provider_slug'] ) && 'rsvp' === $attendee['provider_slug'] ) {
	return;
}

?>
<div class="tec-tickets__admin-attendees-modal-section tec-tickets__admin-attendees-modal-section--order-info">

	<h3 class="tribe-common-h5">
		<?php esc_html_e( 'Purchase information', 'event-tickets' ); ?>
	</h3>

	<div class="tribe-common-g-row tribe-common-g-row--gutters tec-tickets__admin-attendees-modal-attendee-info-row">
		<?php $this->template( 'attendees/modal/attendee/order-info/id' ); ?>
		<?php $this->template( 'attendees/modal/attendee/order-info/date' ); ?>
	</div>
	<div class="tribe-common-g-row tribe-common-g-row--gutters tec-tickets__admin-attendees-modal-attendee-info-row">
		<?php $this->template( 'attendees/modal/attendee/order-info/status' ); ?>
		<?php $this->template( 'attendees/modal/attendee/order-info/ticket' ); ?>
	</div>
</div>
