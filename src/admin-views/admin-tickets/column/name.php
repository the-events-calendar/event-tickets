<?php
/**
 * Table column template.
 *
 * @since 5.14.0
 *
 * @version 5.14.0
 *
 * @var \Tribe__Template  $this         Current template object.
 * @var string            $icon_html    The list table for the All Tickets screen.
 * @var string            $ticket_link  The name of the ticket.
 */

?>
<div class="tec-tickets-admin-tickets-table-column-header-name">
	<div class="tec-tickets-admin-tickets-table-column-header-name-icon">
		<?php echo wp_kses_post( $icon_html ); ?>
	</div>
	<div class="tec-tickets-admin-tickets-table-column-header-name-ticket-name">
		<?php echo wp_kses_post( $ticket_link ); ?>
	</div>
</div>
