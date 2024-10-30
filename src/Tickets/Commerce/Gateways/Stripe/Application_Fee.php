<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use TEC\Tickets\Commerce\Settings;
use TEC\Tickets\Commerce\Utils\Value;

/**
 * The Stripe Application_Fee class
 *
 * @package TEC\Tickets\Commerce\Gateways\Stripe
 */
class Application_Fee {

	/**
	 * The percentage applied to Stripe transactions. Currently set at 2%.
	 *
	 * @since 5.3.0
	 *
	 * @var float
	 */
	const FIXED_FEE = 0.02;

	/**
	 * Calculate the fee value that needs to be applied to the PaymentIntent.
	 *
	 * @since 5.3.0
	 *
	 * @param Value $value the value over which to calculate the fee.
	 *
	 * @return Value;
	 */
	public static function calculate( Value $value ) {
		if ( Settings::is_licensed_plugin( true ) ) {
			return Value::create();
		}

		// Otherwise, calculate it over the total value.
		return Value::create( $value->get_decimal() * static::get_application_fee_percentage() );
	}

	/**
	 * Returns the application fee percentage value.
	 *
	 * @since 5.3.0
	 *
	 * @return float
	 */
	private static function get_application_fee_percentage() {
		return static::FIXED_FEE;
	}
}