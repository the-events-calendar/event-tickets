<?php
/**
 * Ticket type template.
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var string $has_ticket Whether the event has Tickets enabled.
 */

if ( empty( $has_ticket ) ) {
	return;
}
?>
<div class="tec-tickets__series_attached_ticket-type">
	<div class="tec-tickets__series_attached_ticket-type__icon tec-tickets__series_attached_ticket-type__icon--ticket"></div>
	<div class="tickets__series_attached_ticket-type__title">
		<?php
			echo esc_html( tribe_get_ticket_label_singular() );
		?>
	</div>
</div>