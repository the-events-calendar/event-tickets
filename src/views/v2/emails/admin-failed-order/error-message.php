<?php
/**
 * Event Tickets Emails: Failed Order Template.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/emails/admin-failed-order/error-message.php
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
 */



?>
<tr>
	<td style="font-size:14px;font-weight:400;padding-top:10px">
		The following attempted purchase has failed because:
	</td>
</tr>
<tr>
	<td style="color:#da394d;font-size:14px;font-weight:700;padding:24px 0 40px">
		Stripe payment processing was unsuccessful
	</td>
</tr>