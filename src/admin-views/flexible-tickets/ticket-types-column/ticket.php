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
<div class="tec-tickets__series_attached_ticket_types__label">
	<?php
		tribe( 'tickets.admin.views' )->template( 'editor/icons/ticket' );
		echo esc_html( tribe_get_ticket_label_singular() );
	?>
</div>