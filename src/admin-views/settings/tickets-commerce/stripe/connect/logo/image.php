<?php
/**
 * The Template for displaying the Tickets Commerce Stripe Settings, the Stripe logo specifically.
 *
 * @version TBD
 *
 * @since TBD
 *
 * @var Tribe__Tickets__Admin__Views                  $this               [Global] Template object.
 * @var string                                        $plugin_url         [Global] The plugin URL.
 * @var TEC\Tickets\Commerce\Gateways\Stripe\Merchant $merchant           [Global] The merchant class.
 * @var TEC\Tickets\Commerce\Gateways\Stripe\Signup   $signup             [Global] The Signup class.
 * @var bool                                          $is_merchant_active [Global] Whether the merchant is active or not.
 */

$image_src = tribe_resource_url( 'images/admin/stripe-logo.png', false, null, Tribe__Tickets__Main::instance() );

?>

<img
	width="200" <?php // @todo remove this to style properly ?>
	src="<?php echo esc_url( $image_src ); ?>"
	alt="<?php esc_attr_e( 'Stripe Logo Image', 'event-tickets' ); ?>"
	class="tec-tickets__admin-settings-tickets-commerce-stripe-logo-image"
>
