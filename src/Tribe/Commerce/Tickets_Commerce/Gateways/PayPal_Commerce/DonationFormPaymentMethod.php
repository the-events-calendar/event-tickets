<?php

namespace TEC\PaymentGateways\PayPalCommerce;

/**
 * Class DonationFormPaymentMethod
 *
 * @since TBD
 * @package TEC\PaymentGateways\PayPalCommerce
 *
 */
class DonationFormPaymentMethod {

	/**
	 *  Setup filter hook.
	 *
	 * @since TBD
	 */
	public function handle() {
		// Exit.
		if ( ! Utils::gatewayIsActive() ) {
			return;
		}

		add_filter( 'give_enabled_payment_gateways', [ $this, 'filterEnabledPayments' ], 99 );
	}

	/**
	 * Disable PayPal payment option if gateway account is not setup.
	 *
	 * @sicne 2.9.6
	 *
	 * @param array $gateways
	 *
	 * @return array
	 */
	public function filterEnabledPayments( $gateways ) {
		if ( ! array_key_exists( PayPalCommerce::GATEWAY_ID, $gateways ) ) {
			return $gateways;
		}

		if ( ! Utils::isAccountReadyToAcceptPayment() ) {
			unset( $gateways[ PayPalCommerce::GATEWAY_ID ] );
		}

		return $gateways;
	}
}
