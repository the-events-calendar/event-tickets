<?php
/**
 * Event Tickets Emails: Order Ticket Totals - Header Row
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/emails/template-parts/body/order/ticket-totals/header-row.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 *
 * @version TBD
 *
 * @since TBD
 *
 * @var Tribe_Template  $this  Current template object.
 * @var Module           $provider              [Global] The tickets provider instance.
 * @var string           $provider_id           [Global] The tickets provider class name.
 * @var array            $order                 [Global] The order object.
 * @var int              $order_id              [Global] The order ID.
 * @var bool             $is_tec_active         [Global] Whether `The Events Calendar` is active or not.
 */

?>
<tr class="tec-tickets__email-table-content-order-ticket-totals-header-row">
	<th style="width: 80%" class="tec-tickets__email-table-content-order-ticket-totals-cell tec-tickets__email-table-content-order-align-left" align="left">
		<?php echo esc_html__( 'Ticket', 'event-tickets' ); ?>
	</th>
	<th class="tec-tickets__email-table-content-order-ticket-totals-cell tec-tickets__email-table-content-order-align-center" align="center">
		<?php echo esc_html__( 'Qty', 'event-tickets' ); ?>
	</th>
	<th class="tec-tickets__email-table-content-order-ticket-totals-cell tec-tickets__email-table-content-order-align-right" align="right">
		<?php echo esc_html__( 'Price', 'event-tickets' ); ?>
	</th>
</tr>
