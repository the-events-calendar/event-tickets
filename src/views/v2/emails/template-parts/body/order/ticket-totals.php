<?php
/**
 * Event Tickets Emails: Order Ticket Totals
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/emails/template-parts/body/order/ticket-totals.php
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
	<td>
		<table style="border-collapse:collapse;margin-top:10px">
			<?php $this->template( 'template-parts/body/order/ticket-totals/header-row' ); ?>
			<?php $this->template( 'template-parts/body/order/ticket-totals/ticket-row' ); ?>
		</table>
	</td>
</tr>