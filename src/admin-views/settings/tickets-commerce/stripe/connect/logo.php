<?php
/**
 * The Template for displaying the Tickets Commerce Stripe logo and features.
 *
 * @since   5.3.0
 *
 * @version 5.3.0
 *
 * @var string                                        $plugin_url      [Global] The plugin URL.
 * @var TEC\Tickets\Commerce\Gateways\Stripe\Signup   $signup          [Global] The Signup class.
 * @var TEC\Tickets\Commerce\Gateways\Stripe\Merchant $merchant        [Global] The Signup class.
 * @var array                                         $merchant_status [Global] Merchant Status data.
 */

?>

<div class="tec-tickets__admin-settings-tickets-commerce-gateway-logo">

	<?php $this->template( 'settings/tickets-commerce/stripe/connect/logo/image' ); ?>

	<?php $this->template( 'settings/tickets-commerce/stripe/connect/logo/features' ); ?>

</div>
