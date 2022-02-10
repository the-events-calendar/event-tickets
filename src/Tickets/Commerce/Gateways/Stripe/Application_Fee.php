<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use TEC\Tickets\Commerce\Settings;
use TEC\Tickets\Commerce\Utils\Value;

class Application_Fee {

	/**
	 * The percentage applied to Stripe transactions. Currently set at 2%.
	 *
	 * @since TBD
	 *
	 * @var float
	 */
	const FIXED_FEE = 0.02;

	/**
	 * Calculate the fee value that needs to be applied to the PaymentIntent.
	 *
	 * @since TBD
	 *
	 * @param Value $value
	 *
	 * @return Value;
	 */
	public static function calculate( Value $value ) {

		if ( Settings::is_licensed_plugin() ) {
			return Value::create();
		}

		// otherwise, calculate it over the cart total
		return Value::create( $value->get_decimal() * static::get_application_fee_percentage() );
	}

	/**
	 * Returns the application fee percentage value
	 *
	 * @since TBD
	 *
	 * @return float
	 */
	private static function get_application_fee_percentage() {
		return static::FIXED_FEE;
	}
}