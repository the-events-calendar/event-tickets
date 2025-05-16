<?php
/**
 * The Template for displaying the Tickets Commerce PayPal Settings signup link.
 *
 * @version 5.6.4
 *
 * @since 5.6.4
 *
 * @var Tribe__Tickets__Admin__Views                  $this                  [Global] Template object.
 * @var string                                        $plugin_url            [Global] The plugin URL.
 * @var TEC\Tickets\Commerce\Gateways\PayPal\Merchant $merchant              [Global] The merchant class.
 * @var TEC\Tickets\Commerce\Gateways\PayPal\Signup   $signup                [Global] The Signup class.
 * @var bool                                          $is_merchant_active    [Global] Whether the merchant is active or not.
 * @var bool                                          $is_merchant_connected [Global] Whether the merchant is connected or not.
 */

defined( 'ABSPATH' ) || exit;

if ( ! is_ssl() ) {
	return;
}

?>
<div class="tec-tickets__admin-settings-tickets-commerce-gateway-signup-links">
	<?php echo $signup->get_link_html(); // phpcs:ignore ?>
</div>
<?php
