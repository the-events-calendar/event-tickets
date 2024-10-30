<?php
/**
 * The Template for displaying the Tickets Commerce PayPal connection details.
 *
 * @version 5.4.0
 *
 * @since   5.1.10
 * @since   5.3.0 Using generic CSS classes for gateway instead of PayPal.
 * @since   5.4.0 Using the new tickets settings get_url() method.
 *
 * @var Tribe__Tickets__Admin__Views                  $this               [Global] Template object.
 * @var string                                        $plugin_url         [Global] The plugin URL.
 * @var TEC\Tickets\Commerce\Gateways\PayPal\Merchant $merchant           [Global] The merchant class.
 * @var TEC\Tickets\Commerce\Gateways\PayPal\Signup   $signup             [Global] The Signup class.
 * @var bool                                          $is_merchant_active    [Global] Whether the merchant is active or not.
 * @var bool                                          $is_merchant_connected [Global] Whether the merchant is connected or not.
 * @var string                                        $gateway_key           [Global] Key for gateway.
 */

if ( empty( $is_merchant_connected ) ) {
	return;
}

$name           = $merchant->get_merchant_id();
$disconnect_url = $merchant->get_disconnect_url();

?>
<div class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-row">
	<div class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-col1">
		<?php esc_html_e( 'Connected as:', 'event-tickets' ); ?>
	</div>
	<div class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-col2">
		<span class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-text-name">
			<?php echo esc_html( $name ); ?>
		</span>
		<a
			href="<?php echo esc_url( $disconnect_url ); ?>"
			class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-text-disconnect-link"
		>
			<?php esc_html_e( 'Disconnect', 'event-tickets' ); ?>
		</a>
	</div>
</div>
