<?php
/**
 * Event Tickets Emails: Order Total
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/emails/template-parts/body/order/order-total.php
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
<tr>
	<td style="padding-top:20px;text-align:right" align="right">
		<table style="display:inline-block;width:auto">
			<tr>
				<td style="font-size:14px;font-weight:400;line-height:24px;padding-right:10px">Order Total</td>
				<td style="font-size:16px;font-weight:700;line-height:24px;">$100.00</td>
			</tr>
		</table>
	</td>
</tr>