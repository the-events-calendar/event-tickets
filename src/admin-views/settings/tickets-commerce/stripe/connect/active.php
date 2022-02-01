<?php
/**
 * The Template for displaying the Tickets Commerce Stripe Settings when connected.
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

if ( false === $merchant_status['connected'] ) {
	return;
}
?>

<div class="tec-tickets__admin-settings-tickets-commerce-stripe-connected">

	<?php $this->template( 'settings/tickets-commerce/stripe/connect/active/stripe-status' ); ?>

	<?php $this->template( 'settings/tickets-commerce/stripe/connect/active/connection' ); ?>

	<?php $this->template( 'settings/tickets-commerce/stripe/connect/help-links' ); ?>

</div>
