<?php

namespace Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce;

use Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce\SDK_Interface\Utils;

/**
 * Class PaymentFormPaymentMethod
 *
 * @since TBD
 * @package Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce
 *
 */
class PaymentFormPaymentMethod {

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
	 * @since TBD
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
