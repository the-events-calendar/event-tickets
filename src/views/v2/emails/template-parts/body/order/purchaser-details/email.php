<?php
/**
 * Event Tickets Emails: Order Purchaser Details - Email
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/emails/template-parts/body/order/purchaser-details/email.php
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

$purchaser_email = empty( $order->purchaser ) || empty( $order->purchaser['email'] ) ? '' : $order->purchaser['email'];

?>
<td class="tec-tickets__email-table-content-order-purchaser-details-bottom tec-tickets__email-table-content-align-right" align="right">
	<?php esc_html_e( $purchaser_email ); ?>
</td>
