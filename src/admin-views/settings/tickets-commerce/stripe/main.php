<?php
/**
 * The Template for displaying the Tickets Commerce Stripe Settings.
 *
 * @since   5.3.0
 *
 * @todo FrontEnd needs to revisit all of these templates to make sure we're not missing anything
 *
 * @version 5.3.0
 *
 * @var string                                        $plugin_url      [Global] The plugin URL.
 * @var TEC\Tickets\Commerce\Gateways\Stripe\Signup   $signup          [Global] The Signup class.
 * @var TEC\Tickets\Commerce\Gateways\Stripe\Merchant $merchant        [Global] The Signup class.
 * @var array                                         $merchant_status [Global] Merchant Status data.
 */

$classes = [
	'tec-tickets__admin-settings-tickets-commerce-gateway',
	'tec-tickets__admin-settings-tickets-commerce-gateway--connected' => $merchant_status['connected'],
]
?>

<div <?php tribe_classes( $classes ); ?>>
	<div
		id="tec-tickets__admin-settings-tickets-commerce-gateway-connect"
		class="tec-tickets__admin-settings-tickets-commerce-gateway-connect"
	>

		<?php $this->template( 'settings/tickets-commerce/stripe/connect/inactive' ); ?>

		<?php $this->template( 'settings/tickets-commerce/stripe/connect/active' ); ?>

	</div>

	<?php $this->template( 'settings/tickets-commerce/stripe/connect/logo' ); ?>

</div>

<?php $this->template( 'settings/tickets-commerce/stripe/modal/signup-complete' ); ?>
