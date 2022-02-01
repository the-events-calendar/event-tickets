<?php
/**
 * The Template for displaying the Tickets Commerce Stripe help links.
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

?>

<div class="tec-tickets__admin-settings-tickets-commerce-stripe-help-links">

	<?php $this->template( 'settings/tickets-commerce/stripe/connect/help-links/configuring' ); ?>

	<?php $this->template( 'settings/tickets-commerce/stripe/connect/help-links/troubleshooting' ); ?>

</div>
