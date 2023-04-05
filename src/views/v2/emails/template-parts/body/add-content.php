<?php
/**
 * Event Tickets Emails: Main template > Body > Additional Content.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/emails/template-parts/body/add-content.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 *
 * @version TBD
 *
 * @since TBD
 *
 * @var Tribe_Template   $this  Current template object.
 * @var Module           $provider              [Global] The tickets provider instance.
 * @var string           $provider_id           [Global] The tickets provider class name.
 * @var \WP_Post         $order                 [Global] The order object.
 * @var int              $order_id              [Global] The order ID.
 * @var bool             $is_tec_active         [Global] Whether `The Events Calendar` is active or not.
 */


?>
<tr>
	<td class="tec-tickets__email-table-content-add-content-container">
		<h2 style="font-size:16px;font-weight:700;margin-bottom:10px">Additional Information</h2>
		<p>
			Please bring valid ID when attending the show.  Order cancellations are accepted up to 
			10 days prior to the event date.  Email us at <a href="#">customerservice@exitin.com</a> for ticket transfers 
			or refunds.
		</p>
	</td>
</tr>
