<?php
/**
 * Event Tickets Emails: Completed Order Template.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/emails/admin-new-order/ticket-totals.php
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


?>
<tr>
	<td>
		<table style="border-collapse:collapse;margin-top:10px">
			<tr style="border:1px solid #d5d5d5;color: #727272;font-size:12px;font-weight:400;line-height:24px;">
				<td style="padding:0 6px;text-align:left;width:90%" align="left">Ticket</td>
				<td style="padding:0 6px;text-align:center;width:10%" align="center">Qty</td>
				<td style="padding:0 6px;text-align:right;width:10%" align="right">Price</td>
			</tr>
			<tr style="border:1px solid #d5d5d5;font-size:14px;font-weight:400;line-height:24px;">
				<td style="padding:0 6px;text-align:left" align="left">General Admission</td>
				<td style="padding:0 6px;text-align:center" align="center">2</td>
				<td style="padding:0 6px;text-align:right" align="right">$50.00</td>
			</tr>
		</table>
	</td>
</tr>