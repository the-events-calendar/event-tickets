<?php
/**
 * Handles the application fee calculations for Square transactions.
 *
 * This class manages the application fee calculations for Square transactions,
 * specifically for US-based merchants. The fee is only applied to unlicensed
 * installations and US-based sales.
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square
 */

namespace TEC\Tickets\Commerce\Gateways\Square;

use TEC\Tickets\Commerce\Settings;
use TEC\Tickets\Commerce\Utils\Value;

/**
 * The Square Application_Fee class
 *
 * @package TEC\Tickets\Commerce\Gateways\Square
 */
class Application_Fee {

	/**
	 * The percentage applied to Square transactions. Currently set at 2%.
	 *
	 * @since 5.24.0
	 *
	 * @var float
	 */
	const FIXED_FEE = 0.02;

	/**
	 * Calculate the fee value that needs to be applied to the PaymentIntent.
	 *
	 * @since 5.24.0
	 *
	 * @param Value $value the value over which to calculate the fee.
	 *
	 * @return Value;
	 */
	public static function calculate( Value $value ) {
		// Check if merchant is in the US - Square application fees only apply to US sales.
		$merchant         = tribe( Merchant::class );
		$merchant_country = $merchant->get_merchant_country();

		if ( 'US' !== $merchant_country ) {
			return Value::create();
		}

		if ( Settings::is_licensed_plugin( true ) ) {
			return Value::create();
		}

		// Otherwise, calculate it over the total value.
		return Value::create( $value->get_decimal() * static::get_application_fee_percentage() );
	}

	/**
	 * Returns the application fee percentage value.
	 *
	 * @since 5.24.0
	 *
	 * @return float
	 */
	private static function get_application_fee_percentage() {
		return static::FIXED_FEE;
	}
}
