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
 * @var array            $order                 [Global] The order object.
 * @var int              $order_id              [Global] The order ID.
 * @var bool             $is_tec_active         [Global] Whether `The Events Calendar` is active or not.
 */
 
if ( empty( $order ) || empty( $order['total'] ) ) {
	return;
}

?>
<tr>
	<td class="tec-tickets__email-table-content-order-total-container" align="right">
		<table class="tec-tickets__email-table-content-order-total-table">
			<tr>
				<td class="tec-tickets__email-table-content-order-total-left-cell">
					<?php echo esc_html__( 'Order Total', 'event-tickets' ); ?>
				</td>
				<td class="tec-tickets__email-table-content-order-total-right-cell">
					<?php echo esc_html( $order['total'] ); ?>
				</td>
			</tr>
		</table>
	</td>
</tr>