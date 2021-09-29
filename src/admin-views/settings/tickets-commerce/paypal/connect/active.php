<?php
/**
 * The Template for displaying the Tickets Commerce PayPal Settings when connected.
 *
 * @version 5.1.10
 *
 * @since 5.1.10
 *
 * @var Tribe__Tickets__Admin__Views                  $this               [Global] Template object.
 * @var string                                        $plugin_url         [Global] The plugin URL.
 * @var TEC\Tickets\Commerce\Gateways\PayPal\Merchant $merchant           [Global] The merchant class.
 * @var TEC\Tickets\Commerce\Gateways\PayPal\Signup   $signup             [Global] The Signup class.
 * @var bool                                          $is_merchant_active [Global] Whether the merchant is active or not.
 */

if ( empty( $is_merchant_active ) ) {
	return;
}


$refresh_url           = Tribe__Settings::instance()->get_url( [ 'tab' => 'payments', 'tc-action' => 'paypal-refresh-access-token' ] );
$refresh               = ' <a href="' . esc_url( $refresh_url ) . '">' . esc_html__( 'Refresh Access Token', 'event-tickets' ) . '</a>';

$refresh_user_info_url = Tribe__Settings::instance()->get_url( [ 'tab' => 'payments', 'tc-action' => 'paypal-refresh-user-info' ] );
$refresh_user_info     = ' <a href="' . esc_url( $refresh_user_info_url ) . '">' . esc_html__( 'Refresh User Info', 'event-tickets' ) . '</a>';

$refresh_webhook_url = Tribe__Settings::instance()->get_url( [ 'tab' => 'payments', 'tc-action' => 'paypal-refresh-webhook' ] );
$refresh_webhook     = ' <a href="' . esc_url( $refresh_webhook_url ) . '">' . esc_html__( 'Refresh Webhook', 'event-tickets' ) . '</a>';

?>

<div class="tec-tickets__admin-settings-tickets-commerce-paypal-connected">

	<?php $this->template( 'settings/tickets-commerce/paypal/connect/active/paypal-status' ); ?>

	<?php $this->template( 'settings/tickets-commerce/paypal/connect/active/connection' ); ?>


	<p><?php echo $refresh . $refresh_user_info . $refresh_webhook; // phpcs:ignore  ?></p>

</div>
