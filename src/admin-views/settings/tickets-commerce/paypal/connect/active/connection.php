<?php
/**
 * The Template for displaying the Tickets Commerce PayPal connection details.
 *
 * @version TBD
 *
 * @since   5.1.10
 * @since   TBD Using generic CSS classes for gateway instead of PayPal.
 *
 * @var Tribe__Tickets__Admin__Views                  $this               [Global] Template object.
 * @var string                                        $plugin_url         [Global] The plugin URL.
 * @var TEC\Tickets\Commerce\Gateways\PayPal\Merchant $merchant           [Global] The merchant class.
 * @var TEC\Tickets\Commerce\Gateways\PayPal\Signup   $signup             [Global] The Signup class.
 * @var bool                                          $is_merchant_active    [Global] Whether the merchant is active or not.
 * @var bool                                          $is_merchant_connected [Global] Whether the merchant is connected or not.
 * @var string                                        $gateway_key           [Global] Key for gateway.
 */

use TEC\Tickets\Commerce\Payments_Tab;

if ( empty( $is_merchant_connected ) ) {
	return;
}

$name           = $merchant->get_merchant_id();
$disconnect_url = Tribe__Settings::instance()->get_url( [ 
	'tab' => Payments_Tab::$slug,
	Payments_Tab::$key_current_section_get_var => $gateway_key,
	'tc-action' => 'paypal-disconnect'
] );

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
