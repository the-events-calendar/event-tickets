<?php
/**
 * Event Tickets Emails: Failed Order Template.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/emails/admin-failed-order/purchaser-details.php
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
	<td style="padding-bottom:16px">
		Order details:
	</td>
</tr>
<tr>
	<td>
		<table>
			<tr>
				<th style="font-size:16px;font-weight:700;line-height:23px;text-align:left" align="left">
					Order #123
				</th>
				<th style="font-size:16px;font-weight:700;line-height:23px;text-align:right" align="right">
					David Hickox
				</th>
			</tr>
			<tr>
				<td style="font-size:14px;font-weight:400;line-height:23px;text-align:left" align="left">
					March 1, 2023
				</td>
				<td style="font-size:14px;font-weight:400;line-height:23px;text-align:right" align="right">
					david@theeventscalendar.com
				</td>
			</tr>
		</table>
	</td>
</tr>