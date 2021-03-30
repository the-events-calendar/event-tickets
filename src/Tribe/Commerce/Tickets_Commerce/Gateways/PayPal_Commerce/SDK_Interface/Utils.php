<?php

namespace TEC\PaymentGateways\PayPalCommerce\SDK_Interface;

use TEC\PaymentGateways\PayPalCommerce\SDK\Models\MerchantDetail;

/**
 * Class Utils
 *
 * @since TBD
 */
class Utils {

	/**
	 * Returns whether or not the PayPal Commerce gateway is active
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public static function gatewayIsActive() {
		// @todo Add something to ET to check if a provider's "gateway" is active.
		return give_is_gateway_active( PayPalCommerce::GATEWAY_ID );
	}

	/**
	 * Return whether or not payment gateway accept payment.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public static function isAccountReadyToAcceptPayment() {
		/* @var MerchantDetail $merchantDetail */
		$merchantDetail = tribe( MerchantDetail::class );

		return (bool) $merchantDetail->accountIsReady;
	}
}
