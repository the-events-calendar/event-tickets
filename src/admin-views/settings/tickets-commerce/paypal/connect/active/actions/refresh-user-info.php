<?php
/**
 * The Template for displaying the Tickets Commerce refresh user info action button.
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
 */

$debug = defined( 'WP_DEBUG' ) && WP_DEBUG;

if ( empty( $is_merchant_connected ) || ! $debug ) {
	return;
}

$url = Tribe__Settings::instance()->get_url( [ 'tab' => 'payments', 'tc-action' => 'paypal-refresh-user-info' ] );
?>

<a href="<?php echo esc_url( $url ); ?>"><?php esc_html_e( 'Refresh User Info', 'event-tickets' ); ?></a>
