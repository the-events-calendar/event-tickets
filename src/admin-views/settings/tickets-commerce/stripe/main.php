<?php
/**
 * The Template for displaying the Tickets Commerce Stripe Settings.
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

$classes = [
		'tec-tickets__admin-settings-tickets-commerce-stripe',
		'tec-tickets__admin-settings-tickets-commerce-stripe--connected' => $merchant_status['connected'],
]
?>

<div <?php tribe_classes( $classes ); ?> style="border: 1px solid black;">
	<div id="tec-tickets__admin-settings-tickets-commerce-stripe-connect"
		 class="tec-tickets__admin-settings-tickets-commerce-stripe-connect">

		<?php $this->template( 'settings/tickets-commerce/stripe/connect/inactive' ); ?>

		<?php $this->template( 'settings/tickets-commerce/stripe/connect/active' ); ?>

	</div>

	<?php $this->template( 'settings/tickets-commerce/stripe/connect/logo' ); ?>

</div>

<?php $this->template( 'settings/tickets-commerce/stripe/modal/signup-complete' ); ?>
