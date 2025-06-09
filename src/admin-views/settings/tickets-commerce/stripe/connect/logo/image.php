<?php
/**
 * The Template for displaying the Tickets Commerce Stripe Settings, the PayPal logo specifically.
 *
 * @since 5.3.0
 *
 * @version 5.3.0
 *
 * @var string                                        $plugin_url      [Global] The plugin URL.
 * @var TEC\Tickets\Commerce\Gateways\Stripe\Signup   $signup          [Global] The Signup class.
 * @var TEC\Tickets\Commerce\Gateways\Stripe\Merchant $merchant        [Global] The Signup class.
 * @var array                                         $merchant_status [Global] Merchant Status data.
 */

$image_src = tribe_resource_url( 'images/admin/stripe-logo.png', false, null, Tribe__Tickets__Main::instance() );

?>

<img
	width="200" <?php // @todo remove this to style properly ?>
	src="<?php echo esc_url( $image_src ); ?>"
	alt="<?php esc_attr_e( 'Stripe Logo Image', 'event-tickets' ); ?>"
	class="tec-tickets__admin-settings-tickets-commerce-gateway-logo-image tec-tickets__admin-settings-tickets-commerce-gateway-logo-image--stripe"
>
