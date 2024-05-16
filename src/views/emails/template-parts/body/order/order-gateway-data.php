<?php
/**
 * Event Tickets Emails: Gateway Data
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/emails/template-parts/body/order/order-gateway-data.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 *
 * @version 5.6.0
 *
 * @since 5.6.0
 * @since 5.10.0 Don't show if gateway order number is same as regular order number.
 *
 * @var Tribe__Template                    $this               Current template object.
 * @var \TEC\Tickets\Emails\Email_Abstract $email              The email object.
 * @var \WP_Post                           $order              The order object.
 */

use TEC\Tickets\Commerce\Gateways\Manager;

if ( empty( $order )  ) {
	return;
}

if ( empty( $order->gateway_order_id )  ) {
	return;
}

// No need to show gateway ID if it's the same as the order ID.
if ( is_numeric( $order->gateway_order_id ) && intval( $order->ID ) === intval( $order->gateway_order_id ) ) {
	return;
}

$gateway = tribe( Manager::class )->get_gateway_by_key( $order->gateway );
$link_or_id = $order->gateway_order_id;
if ( $gateway ) {
	$link_or_id = sprintf(
		'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
		esc_url( $gateway->get_order_controller()->get_gateway_dashboard_url_by_order( $order ) ),
		$order->gateway_order_id
	);
}

// In this case we specifically escape before sprintf, because we want the link in the translation.
$gateway_order_id_string = sprintf(
	// Translators: %s - The order gateway ID.
	esc_html__( 'Gateway Order #%s', 'event-tickets' ),
	$link_or_id
);

?>
<tr>
	<td class="tec-tickets__email-table-content-order-gateway-data-container" align="right">
		<?php echo $gateway_order_id_string; ?>
	</td>
</tr>