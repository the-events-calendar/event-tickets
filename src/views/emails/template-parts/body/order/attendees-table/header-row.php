<?php
/**
 * Event Tickets Emails: Order Attendees Table Header Row
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/emails/template-parts/body/order/attendees-table/header-row.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 *
 * @version 5.5.11
 *
 * @since 5.5.11
 *
 * @var \Tribe__Template $this  Current template object.
 * @var array            $order                 [Global] The order object.
 * @var bool             $is_tec_active         [Global] Whether `The Events Calendar` is active or not.
 */

?>
<tr class="tec-tickets__email-table-content-order-attendees-table-header-row">
	<td class="tec-tickets__email-table-content-order-attendees-table-header-cell tec-tickets__email-table-content-align-left" align="left">
		<?php echo esc_html__( 'Attendee', 'event-tickets' ); ?>
	</td>
	<td class="tec-tickets__email-table-content-order-attendees-table-header-cell tec-tickets__email-table-content-align-center" align="center">
		<?php echo esc_html__( 'Name', 'event-tickets' ); ?>
	</td>
	<td class="tec-tickets__email-table-content-order-attendees-table-header-cell tec-tickets__email-table-content-align-right" align="right">
		<?php echo esc_html__( 'Ticket ID', 'event-tickets' ); ?>
	</td>
</tr>
