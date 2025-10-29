<?php
/**
 * Gateway Value
 *
 * Handles normalization of monetary values for gateway processing.
 * This class provides a unified wrapper around Precision_Value that applies
 * gateway-specific currency normalization logic through each gateway’s
 * `normalize_value_for_gateway()` implementation.
 *
 * Gateway_Value ensures all values sent to payment gateways are formatted
 * according to their precision rules without mutating display values.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Values
 */
declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Values;

use TEC\Tickets\Commerce\Gateways\Contracts\Gateway_Interface;

/**
 * Gateway_Value
 *
 * Represents a value normalized for gateway use.
 *
 * @since TBD
 */
class Gateway_Value implements Value_Interface {

	/**
	 * The underlying normalized precision value.
	 *
	 * @var Precision_Value
	 */
	protected Precision_Value $value;

	/**
	 * The gateway instance.
	 *
	 * @since TBD
	 *
	 * @var Gateway_Interface
	 */
	protected Gateway_Interface $gateway;

	/**
	 * Gateway_Value constructor.
	 *
	 * @since TBD
	 *
	 * @param Precision_Value   $value Display or raw precision value.
	 * @param string            $currency_code ISO currency code (USD, JPY, etc).
	 * @param Gateway_Interface $gateway Gateway.
	 */
	public function __construct(
		Gateway_Interface $gateway,
		Precision_Value   $value,
		string            $currency_code
	) {
		$this->gateway = $gateway;
		$currency_code = strtoupper( $currency_code );
		$this->value   = $gateway->normalize_value_for_gateway( $value, $currency_code );
	}

	/**
	 * Get the normalized float value.
	 *
	 * @since TBD
	 *
	 * @return float
	 */
	public function get(): float {
		return $this->value->get();
	}

	/**
	 * Get the normalized integer value for gateway requests.
	 *
	 * @since TBD
	 *
	 * @return int
	 */
	public function get_integer(): int {
		return $this->value->get_as_integer();
	}

	/**
	 * Get the wrapped Precision_Value (useful for fee math).
	 *
	 * @since TBD
	 *
	 * @return Precision_Value
	 */
	public function get_precision_value(): Precision_Value {
		return $this->value;
	}
}
