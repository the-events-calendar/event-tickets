<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use TEC\Tickets\Commerce\Settings;
use TEC\Tickets\Commerce\Utils\Value;
use TEC\Tickets\Commerce\Gateways\Gateway_Value_Formatter;

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
	 * @since TBD Use Gateway_Value_Formatter to ensure proper precision for Stripe API.
	 *
	 * @param Value $value the value over which to calculate the fee.
	 *
	 * @return Value;
	 */
	public static function calculate( Value $value ) {
		if ( Settings::is_licensed_plugin( true ) ) {
			return Value::create();
		}

		// Calculate the fee.
		$fee_decimal = $value->get_decimal() * static::get_application_fee_percentage();

		/*
		Create fee value by cloning the input value and setting the fee amount.
		This is to avoid side effects on the input value, as we're modifying the value.
		*/
		$fee_value = clone $value;
		$fee_value->set_value( $fee_decimal );

		// Format the fee value for Stripe API to ensure proper precision.
		$formatter = new Gateway_Value_Formatter( Gateway::get_key() );
		$fee_value = $formatter->format( $fee_value );

		// Otherwise, calculate it over the total value.
		return $fee_value;
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
