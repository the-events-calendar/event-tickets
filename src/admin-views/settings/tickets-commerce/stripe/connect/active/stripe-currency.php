<?php
/**
 * The Template for displaying the Tickets Commerce Stripe currency.
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

use TEC\Tickets\Commerce\Utils\Currency;

if ( false === $merchant_status['connected'] ) {
	return;
}

if ( empty( $merchant_status['default_currency'] ) ) {
	return;
}

$stripe_currency = strtoupper( $merchant_status['default_currency'] );
$tc_currency     = Currency::get_currency_code();

if ( $stripe_currency !== $tc_currency ) {
	$message = sprintf(
		// Translators: %1$s is the Stripe currency, %2$s is the Tickets Commerce currency symbol.
		__( 'Your Stripe account is set to %1$s, but your Tickets Commerce site is set to %2$s. Please update your Tickets Commerce currency to match the one on Stripe.', 'event-tickets' ),
		'<strong>' . $stripe_currency . '</strong>',
		'<strong>' . $tc_currency . '</strong>'
	);
} else {
	$message = sprintf(
		// Translators: %1$s The opening `<a>` tag with the stripe link, %2$s The closing `</a>` tag.
		__( 'Please be sure to enable all the payment methods you want to use for this currency on your %1$sstripe dashboard%2$s.', 'event-tickets' ),
		'<a href="https://dashboard.stripe.com/settings/payment_methods" target="_blank" rel="noopener noreferrer">',
		'</a>'
	);
}

$message_classes = [
	'tec-tickets__admin-settings-tickets-commerce-gateway-currency-message',
	'tec-tickets__admin-settings-tickets-commerce-gateway-currency-message--error' => $stripe_currency !== $tc_currency,
];

?>
<div class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-row">
	<div class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-col1">
		<?php esc_html_e( 'Stripe currency:', 'event-tickets' ); ?>
	</div>
	<div class="tec-tickets__admin-settings-tickets-commerce-gateway-connected-col2">
		<div class="tec-tickets__admin-settings-tickets-commerce-gateway-currency"><?php echo esc_html( Currency::get_currency_name( $stripe_currency ) ); ?></div>
		<div <?php tribe_classes( $message_classes ); ?>><?php echo wp_kses_post( $message ); ?></div>
	</div>
</div>
