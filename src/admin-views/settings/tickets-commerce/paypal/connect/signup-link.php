<?php
/**
 * The Template for displaying the Tickets Commerce PayPal Settings sgnup link.
 *
 * @version TBD
 *
 * @since   TBD
 *
 * @var Tribe__Tickets__Admin__Views                  $this                  [Global] Template object.
 * @var string                                        $plugin_url            [Global] The plugin URL.
 * @var TEC\Tickets\Commerce\Gateways\PayPal\Merchant $merchant              [Global] The merchant class.
 * @var TEC\Tickets\Commerce\Gateways\PayPal\Signup   $signup                [Global] The Signup class.
 * @var bool                                          $is_merchant_active    [Global] Whether the merchant is active or not.
 * @var bool                                          $is_merchant_connected [Global] Whether the merchant is connected or not.
 * @var bool                                          $is_ssl                [Global] Whether the site is SSL or not.
 */

if ( ! is_ssl() ) {
	return;
}

?>
<div class="tec-tickets__admin-settings-tickets-commerce-gateway-signup-links">
	<?php echo $signup->get_link_html(); // phpcs:ignore ?>
</div>