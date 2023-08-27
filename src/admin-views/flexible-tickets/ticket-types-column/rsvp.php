<?php
/**
 * RSVP type template.
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var string $has_rsvp Whether the event has RSVP enabled.
 */

if ( empty( $has_rsvp ) ) {
	return;
}
?>
<div class="tec-tickets__series_attached_ticket-type">
	<div class="tec-tickets__series_attached_ticket-type__icon tec-tickets__series_attached_ticket-type__icon--rsvp"></div>
	<div class="tickets__series_attached_ticket-type__title">
		<?php
			echo esc_html( tribe_get_rsvp_label_singular() );
		?>
	</div>
</div>