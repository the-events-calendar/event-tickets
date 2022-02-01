<?php
/**
 * The Template for displaying the Tickets Commerce Stripe help links.
 *
 * @since   TBD
 *
 * @version TBD
 *
 * @var string                                        $plugin_url      [Global] The plugin URL.
 * @var TEC\Tickets\Commerce\Gateways\Stripe\Signup   $signup          [Global] The Signup class.
 * @var TEC\Tickets\Commerce\Gateways\Stripe\Merchant $merchant        [Global] The Signup class.
 * @var array                                         $merchant_status [Global] Merchant Status data.
 */

?>

<div class="tec-tickets__admin-settings-tickets-commerce-stripe-help-links">

	<?php $this->template( 'settings/tickets-commerce/stripe/connect/help-links/configuring' ); ?>

	<?php $this->template( 'settings/tickets-commerce/stripe/connect/help-links/troubleshooting' ); ?>

</div>
