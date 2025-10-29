<?php
/**
 * Gateway Value Formatter
 *
 * @since 5.26.7
 *
 * @package TEC\Tickets\Commerce\Gateways
 */

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Gateways;

use TEC\Tickets\Commerce\Gateways\Contracts\Gateway_Interface;
use TEC\Tickets\Commerce\Utils\Currency;
use TEC\Tickets\Commerce\Utils\Value;
use TEC\Tickets\Commerce\Values\Gateway_Value;
use TEC\Tickets\Commerce\Values\Precision_Value;

/**
 * Gateway Value Formatter
 *
 * Converts Value objects into gateway-specific formatted Value objects
 * without mutating the original Value object.
 *
 * This class acts as a wrapper around Gateway_Value, which handles
 * gateway-specific normalization via normalize_value_for_gateway.
 *
 * @since 5.26.7
 * @since TBD Refactored to use Gateway_Value internally for normalization.
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
	 * Uses Gateway_Value to normalize currency precision according to the
	 * gateway’s rules without altering the original Value object.
	 *
	 * @since 5.26.7
	 * @since TBD Refactored to use Gateway_Value.
	 *
	 * @param Value $value The value to format.
	 *
	 * @return Value A new Value object formatted for gateway processing.
	 */
	public function format( Value $value ): Value {
		$currency_code = strtoupper( $value->get_currency_code() );

		// Convert Value → Precision_Value → Gateway_Value.
		$precision_value = new Precision_Value( $value->get_float(), $value->get_precision() );
		$gateway_value   = new Gateway_Value( $this->gateway, $precision_value, $currency_code );

		// Get the normalized precision from Gateway_Value.
		$normalized_precision_value = $gateway_value->get_precision_value();
		$normalized_precision = $normalized_precision_value->get_precision();

		// Reconstruct a new Value object with the normalized float and precision.
		$formatted_value = new Value( $gateway_value->get() );
		$formatted_value->set_precision( $normalized_precision );
		$formatted_value->update();

		return $formatted_value;
	}

	/**
	 * Format a numeric amount (int or float) for gateway transmission.
	 *
	 * This provides a lightweight alternative for cases where a full Value
	 * object isn’t available or needed.
	 *
	 * @since TBD
	 *
	 * @param float|int $amount        The numeric amount to format.
	 * @param string    $currency_code The ISO currency code (e.g., USD, JPY).
	 *
	 * @return float|int Normalized amount for the gateway.
	 */
	public function format_integer( $amount, string $currency_code ) {
		$currency_code   = strtoupper( $currency_code );
		$precision_value = new Precision_Value( (float) $amount );
		$gateway_value   = new Gateway_Value( $this->gateway, $precision_value, $currency_code );

		// Return integer if currency precision is zero, otherwise float.
		return $gateway_value->get_precision_value()->get_precision() === 0
			? $gateway_value->get_integer()
			: $gateway_value->get();
	}

	/**
	 * Get currency data for backward compatibility with existing filters.
	 *
	 * @deprecated TBD Use Gateway_Value normalization instead.
	 *
	 * @since 5.26.7
	 * @since TBD Updated for Gateway_Value parity.
	 *
	 * @param string $currency_code The currency code.
	 *
	 * @return array The filtered currency data.
	 */
	protected function get_currency_data( string $currency_code ): array {
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
		return apply_filters(
			"tec_tickets_commerce_gateway_value_formatter_{$gateway_key}_currency_map",
			$currency_data,
			$currency_code,
			$gateway_key
		);
	}
}
