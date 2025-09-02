<?php
/**
 * Event Tickets Emails: Order Ticket Totals - Total Row
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/emails/template-parts/body/order/ticket-totals/total-row.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 *
 * @version TBD
 *
 * @since TBD
 *
 * @var Tribe__Template                    $this               Current template object.
 * @var \TEC\Tickets\Emails\Email_Abstract $email              The email object.
 * @var string                             $heading            The email heading.
 * @var string                             $title              The email title.
 * @var bool                               $preview            Whether the email is in preview mode or not.
 * @var string                             $additional_content The email additional content.
 * @var bool                               $is_tec_active      Whether `The Events Calendar` is active or not.
 * @var \WP_Post                           $order              The order object.
 */

if ( empty( $order ) || empty( $order->total_value ) ) {
	return;
}

?>
<tr class="tec-tickets__email-table-content-order-ticket-totals-total-row">
	<td class="tec-tickets__email-table-content-order-ticket-totals-cell tec-tickets__email-table-content-order-align-left" align="left">
		&nbsp;
	</td>
	<td class="tec-tickets__email-table-content-order-ticket-totals-cell tec-tickets__email-table-content-order-ticket-totals-total-cell tec-tickets__email-table-content-order-align-center" align="center">
		<strong><?php echo esc_html__( 'Order Total', 'event-tickets' ); ?></strong>
	</td>
	<td class="tec-tickets__email-table-content-order-ticket-totals-cell tec-tickets__email-table-content-order-ticket-totals-total-cell tec-tickets__email-table-content-order-align-right" align="right">
		<strong><?php echo esc_html( $order->total_value->get() ); ?></strong>
	</td>
</tr>
