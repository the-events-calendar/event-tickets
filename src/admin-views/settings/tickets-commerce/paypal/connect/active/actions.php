<?php
/**
 * The Template for displaying the Tickets Commerce PayPal connection details.
 *
 * @version 5.3.0
 *
 * @since 5.2.0
 * @since 5.3.0 Using generic CSS classes for gateway instead of PayPal.
 *
 * @var Tribe__Tickets__Admin__Views                  $this               [Global] Template object.
 * @var string                                        $plugin_url         [Global] The plugin URL.
 * @var TEC\Tickets\Commerce\Gateways\PayPal\Merchant $merchant           [Global] The merchant class.
 * @var TEC\Tickets\Commerce\Gateways\PayPal\Signup   $signup             [Global] The Signup class.
 * @var bool                                          $is_merchant_active    [Global] Whether the merchant is active or not.
 * @var bool                                          $is_merchant_connected [Global] Whether the merchant is connected or not.
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $is_merchant_connected ) ) {
	return;
}
?>

<div class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-actions">
	<?php $this->template( 'settings/tickets-commerce/paypal/connect/active/actions/refresh-connection' ); ?>

	<div class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-actions-debug">
		<?php $this->template( 'settings/tickets-commerce/paypal/connect/active/actions/refresh-access-token' ); ?>

		<?php $this->template( 'settings/tickets-commerce/paypal/connect/active/actions/refresh-user-info' ); ?>

		<?php $this->template( 'settings/tickets-commerce/paypal/connect/active/actions/refresh-webhook' ); ?>
	</div>
</div>
<?php
