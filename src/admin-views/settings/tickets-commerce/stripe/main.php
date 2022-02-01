<?php
/**
 * The Template for displaying the Tickets Commerce Stripe Settings.
 *
 * @since   TBD
 *
 * @version TBD
 *
 * @var Tribe__Tickets__Admin__Views                  $this                  [Global] Template object.
 * @var string                                        $plugin_url            [Global] The plugin URL.
 * @var TEC\Tickets\Commerce\Gateways\Stripe\Merchant $merchant              [Global] The merchant class.
 * @var TEC\Tickets\Commerce\Gateways\Stripe\Signup   $signup                [Global] The Signup class.
 * @var bool                                          $is_merchant_active    [Global] Whether the merchant is active or
 *      not.
 * @var bool                                          $is_merchant_connected [Global] Whether the merchant is connected
 *      or not.
 */

$is_merchant_active    = false;
$is_merchant_connected = false;

$classes = [
		'tec-tickets__admin-settings-tickets-commerce-stripe',
		'tec-tickets__admin-settings-tickets-commerce-stripe--connected' => $is_merchant_connected,
]
?>

<div <?php tribe_classes( $classes ); ?> style="border: 1px solid black;"> <!-- @todo: We need to move this to the stylesheet. -->
	<div id="tec-tickets__admin-settings-tickets-commerce-stripe-connect"
		 class="tec-tickets__admin-settings-tickets-commerce-stripe-connect">

		<?php $this->template( 'settings/tickets-commerce/stripe/connect/inactive' ); ?>

		<?php $this->template( 'settings/tickets-commerce/stripe/connect/active' ); ?>

	</div>

	<?php $this->template( 'settings/tickets-commerce/stripe/connect/logo' ); ?>

</div>

<?php $this->template( 'settings/tickets-commerce/stripe/modal/signup-complete' ); ?>
