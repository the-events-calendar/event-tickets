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
<div class="tec-tickets__series_attached_ticket_types__label">
	<?php
	tribe( 'tickets.admin.views' )->template( 'editor/icons/rsvp' );
	echo esc_html( tribe_get_rsvp_label_singular() ); ?>
</div>