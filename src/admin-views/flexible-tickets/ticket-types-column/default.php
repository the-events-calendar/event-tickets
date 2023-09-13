<?php
/**
 * Ticket type template.
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var array $tickets Array of default tickets.
 */

if ( empty( $tickets ) ) {
	return;
}
?>
<div class="tec-tickets__series_attached_ticket-type">
	<div class="tec-tickets__series_attached_ticket-type__icon tec-tickets__series_attached_ticket-type__icon--ticket"></div>
	<div class="tickets__series_attached_ticket-type__title">
		<?php
			echo esc_html( tec_tickets_get_default_ticket_type_label() );
		?>
	</div>
</div>