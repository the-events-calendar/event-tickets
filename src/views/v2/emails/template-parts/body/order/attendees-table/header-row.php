<?php
/**
 * Event Tickets Emails: Order Attendees Table Header Row
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/emails/template-parts/body/order/attendees-table/header-row.php
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
 * @var \WP_Post         $order                 [Global] The order object.
 * @var int              $order_id              [Global] The order ID.
 * @var bool             $is_tec_active         [Global] Whether `The Events Calendar` is active or not.
 */

// @todo @codingmusician @juanfra Replace hardcoded data with dynamic data.

?>
<tr style="border:1px solid #d5d5d5;color: #727272;font-size:12px;font-weight:400;line-height:24px;">
	<td style="padding:0 6px;text-align:left;" align="left">
		<?php echo esc_html__( 'Attendee', 'event-tickets' ); ?>
	</td>
	<td style="padding:0 6px;text-align:center;" align="center">
		<?php echo esc_html__( 'Type', 'event-tickets' ); ?>
	</td>
	<td style="padding:0 6px;text-align:right;" align="right">
		<?php echo esc_html__( 'Ticket ID', 'event-tickets' ); ?>
	</td>
</tr>