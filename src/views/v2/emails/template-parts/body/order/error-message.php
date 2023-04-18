<?php
/**
 * Event Tickets Emails: Order Error Message
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/emails/template-parts/body/order/error-message.php
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
 * @var object           $order                 [Global] The order object.
 * @var int              $order_id              [Global] The order ID.
 * @var bool             $is_tec_active         [Global] Whether `The Events Calendar` is active or not.
 */

if ( empty( $order ) || empty( $order->error_message ) ) {
	return;
}

?>
<tr>
	<td class="tec-tickets__email-table-content-order-error-top-text">
		<?php echo esc_html__( 'The following attempted purchase has failed because:', 'event-tickets' ); ?>
	</td>
</tr>
<tr>
	<td class="tec-tickets__email-table-content-order-error-bottom-text">
		<?php echo esc_html( $order->error_message ); ?>
	</td>
</tr>