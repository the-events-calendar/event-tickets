<?php
/**
 * Event Tickets Emails: Order Purchaser Details
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/emails/template-parts/body/order/purchaser-details.php
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

// @todo @codingmusician @juanfra Replace hardcoded data with dynamic data.

if( empty( $order ) ) {
	return;
}

?>
<tr>
	<td>
		<table>
			<tr>
				<?php $this->template( 'template-parts/body/order/purchaser-details/order-number' ); ?>
				<?php $this->template( 'template-parts/body/order/purchaser-details/name' ); ?>
			</tr>
			<tr>
				<?php $this->template( 'template-parts/body/order/purchaser-details/date' ); ?>
				<?php $this->template( 'template-parts/body/order/purchaser-details/email' ); ?>
			</tr>
		</table>
	</td>
</tr>