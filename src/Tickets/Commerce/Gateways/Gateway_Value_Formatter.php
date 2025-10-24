<?php
/**
 * Gateway Value Formatter
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways
 */

namespace TEC\Tickets\Commerce\Gateways;

use TEC\Tickets\Commerce\Utils\Value;
use TEC\Tickets\Commerce\Utils\Currency;

/**
 * Gateway Value Formatter
 *
 * Converts Value objects into gateway-specific formatted Value objects
 * without mutating the original Value object.
 *
 * @since TBD
 */
class Gateway_Value_Formatter {

	/**
	 * The gateway name.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected string $gateway;

	/**
	 * Constructor.
	 *
	 * @since TBD
	 *
	 * @param string $gateway The gateway name.
	 */
	public function __construct( string $gateway ) {
		$this->gateway = $gateway;
	}

	/**
	 * Format a Value object for the specific gateway.
	 *
	 * @since TBD
	 *
	 * @param Value $value The value to format.
	 *
	 * @return Value A new Value object formatted for the gateway.
	 */
	public function format( Value $value ): Value {
		// Get the currency code from the value.
		$currency_code = $value->get_currency_code();

		// Get the currency data from the currency map.
		$currency_data = $this->get_currency_data( $currency_code );

		// Determine the appropriate precision for this gateway and currency.
		$precision = $this->get_gateway_precision( $currency_code, $currency_data );

		// Create a new Value object with the same float value.
		$formatted_value = new Value( $value->get_float() );

		// Set the precision for the gateway.
		$formatted_value->set_precision( $precision );

		// Update the internal values to reflect the new precision.
		$formatted_value->update();

		return $formatted_value;
	}

	/**
	 * Get currency data from the currency map, filtered for this gateway.
	 *
	 * @since TBD
	 *
	 * @param string $currency_code The currency code.
	 *
	 * @return array The filtered currency data.
	 */
	private function get_currency_data( $currency_code ) {
		$currency_map = Currency::get_default_currency_map();
		$currency_data = $currency_map[ $currency_code ] ?? [];

		/**
		 * Filter the currency data for gateway value formatting.
		 *
		 * @since TBD
		 *
		 * @param array  $currency_data The currency data from the map.
		 * @param string $currency_code The currency code.
		 * @param string $gateway       The gateway name.
		 */
		$filter_name = "tec_tickets_commerce_gateway_value_formatter_{$this->gateway}_currency_map";
		return apply_filters( $filter_name, $currency_data, $currency_code, $this->gateway );
	}

	/**
	 * Get the appropriate precision for the gateway and currency.
	 *
	 * @since TBD
	 *
	 * @param string $currency_code The currency code.
	 * @param array  $currency_data The filtered currency data.
	 *
	 * @return int The precision to use.
	 */
	private function get_gateway_precision( $currency_code, $currency_data ) {
		// Use the precision from the filtered currency data.
		// Gateway-specific logic is handled via filters in the respective gateway's Hooks class.
		return $currency_data['decimal_precision'] ?? 2;
	}
}
