<?php
/**
 * The Template for displaying the Tickets Commerce refresh webhook action button.
 *
 * @version 5.4.0
 *
 * @since 5.2.0
 * @since 5.3.0 Added section param to URL.
 * @since 5.3.0 Using generic CSS classes for gateway instead of PayPal.
 * @since 5.4.0 Using the new tickets settings get_url() method.
 * @since 5.23.0 updated the Tab to be the Gateway Key.
 *
 * @var Tribe__Tickets__Admin__Views                  $this               [Global] Template object.
 * @var string                                        $plugin_url         [Global] The plugin URL.
 * @var TEC\Tickets\Commerce\Gateways\PayPal\Merchant $merchant           [Global] The merchant class.
 * @var TEC\Tickets\Commerce\Gateways\PayPal\Signup   $signup             [Global] The Signup class.
 * @var bool                                          $is_merchant_active    [Global] Whether the merchant is active or not.
 * @var bool                                          $is_merchant_connected [Global] Whether the merchant is connected or not.
 * @var string                                        $gateway_key           [Global] Key for gateway.
 */

use Tribe\Tickets\Admin\Settings;
use TEC\Tickets\Commerce\Payments_Tab;

defined( 'ABSPATH' ) || exit;

$debug = defined( 'WP_DEBUG' ) && WP_DEBUG;

if ( empty( $is_merchant_connected ) || ! $debug ) {
	return;
}

$url = tribe( Settings::class )->get_url(
	[
		tribe( Payments_Tab::class )::$key_current_section_get_var => $gateway_key,
		'tc-action' => 'paypal-refresh-webhook',
	]
);
?>
<a
	href="<?php echo esc_url( $url ); ?>"
	class="tec-tickets__admin-settings-tickets-commerce-gateway-refresh-link"
><?php esc_html_e( 'Refresh Webhook', 'event-tickets' ); ?></a>
<?php
