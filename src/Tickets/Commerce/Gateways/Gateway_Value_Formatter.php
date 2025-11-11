<?php
/**
 * Gateway Value Formatter
 *
 * @since 5.26.7
 *
 * @package TEC\Tickets\Commerce\Gateways
 */

namespace TEC\Tickets\Commerce\Gateways;

use TEC\Tickets\Commerce\Utils\Value;
use TEC\Tickets\Commerce\Utils\Currency;
use TEC\Tickets\Commerce\Gateways\Contracts\Gateway_Interface;

/**
 * Gateway Value Formatter
 *
 * Converts Value objects into gateway-specific formatted Value objects
 * without mutating the original Value object.
 *
 * @since 5.26.7
 */
class Gateway_Value_Formatter {

	/**
	 * The gateway instance.
	 *
	 * @since 5.26.7
	 *
	 * @var Gateway_Interface
	 */
	protected Gateway_Interface $gateway;

	/**
	 * Constructor.
	 *
	 * @since 5.26.7
	 *
	 * @param Gateway_Interface $gateway The gateway instance.
	 */
	public function __construct( Gateway_Interface $gateway ) {
		$this->gateway = $gateway;
	}

	/**
	 * Format a Value object for the specific gateway.
	 *
	 * @since 5.26.7
	 *
	 * @param Value $value The value to format.
	 *
	 * @return Value A new Value object formatted for the gateway.
	 */
	public function format( Value $value ): Value {
		// Determine the appropriate precision for this gateway and currency.
		$precision = $this->get_gateway_precision( $value );

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
	 * @since 5.26.7
	 *
	 * @param string $currency_code The currency code.
	 *
	 * @return array The filtered currency data.
	 */
	protected function get_currency_data( $currency_code ) {
		$currency_map  = Currency::get_default_currency_map();
		$currency_data = $currency_map[ $currency_code ] ?? [];
		$gateway_key   = $this->gateway::get_key();

		/**
		 * Filter the currency data for gateway value formatting.
		 *
		 * @since 5.26.7
		 *
		 * @param array  $currency_data The currency data from the map.
		 * @param string $currency_code The currency code.
		 * @param string $gateway The gateway name.
		 */
		return apply_filters( "tec_tickets_commerce_gateway_value_formatter_{$gateway_key}_currency_map", $currency_data, $currency_code, $gateway_key );
	}

	/**
	 * Get the appropriate precision for the gateway and currency.
	 *
	 * @since 5.26.7
	 *
	 * @param Value $value The value to get precision for.
	 *
	 * @return int The precision to use.
	 */
	protected function get_gateway_precision( Value $value ) {
		// Get the currency code from the value.
		$currency_code = $value->get_currency_code();

		// Get the currency data from the currency map.
		$currency_data = $this->get_currency_data( $currency_code );

		// Use the precision from the filtered currency data.
		// Gateway-specific logic is handled via filters in the respective gateway's Hooks class.
		return $currency_data['decimal_precision'] ?? $this->gateway::get_default_currency_precision();
	}
}
