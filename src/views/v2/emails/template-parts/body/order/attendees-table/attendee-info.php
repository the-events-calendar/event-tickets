<?php
/**
 * Event Tickets Emails: Order Attendee Info
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/emails/template-parts/body/order/attendee-info.php
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
if ( empty( $attendee ) ) {
	return;
}

?>
<tr style="border:1px solid #d5d5d5;font-size:12px;font-weight:400;line-height:24px;">
	<td style="padding:0 6px;text-align:left;vertical-align:top" align="left">
		<?php echo $attendee['name']; ?><br>
		<?php echo $attendee['email']; ?><br>
		<?php $this->template( 'template-parts/body/order/attendees-table/custom-fields' ); ?>
	</td>
	<td style="padding:0 6px;text-align:center;vertical-align:top" align="center">
		<?php echo $attendee['ticket_title']; ?><br>
	</td>
	<td style="padding:0 6px;text-align:right;vertical-align:top" align="right">
		<?php echo $attendee['ticket_id']; ?><br>
	</td>
</tr>