<?php
/**
 * Percent Value.
 *
 * @since TBD
 */

declare( strict_types=1 );

namespace TEC\Tickets\Order_Modifiers\Values;

use InvalidArgumentException;

/**
 * Class Percent_Value
 *
 * @since TBD
 */
class Percent_Value extends Precision_Value {

	/**
	 * Percent_Value constructor.
	 *
	 * Numbers passed into this class should be written as a percent, and not a decimal. For
	 * example, for 10% you would pass in 10, not 0.1. For 5% you would pass in 5, not 0.05.
	 *
	 * @since TBD
	 *
	 * @param float|int|string $value The value to store. Can be a float, int, or numeric string. The
	 *                                value will be divided by 100 to convert it to a percentage.
	 */
	public function __construct( $value ) {
		$value = Float_Value::from_number( $value )->get() / 100;
		parent::__construct( $value, 4 );
		$this->validate_value();
	}

	/**
	 * Get the value as a percentage.
	 *
	 * @since TBD
	 *
	 * @return float
	 */
	public function get_as_percent(): float {
		return (float) ( $this->get() * 100 );
	}

	/**
	 * Get the value as a decimal.
	 *
	 * Just an alias for get().
	 *
	 * @since TBD
	 *
	 * @return float
	 */
	public function get_as_decimal(): float {
		return $this->get();
	}

	/**
	 * The __toString method allows a class to decide how it will react when it is converted to a string.
	 *
	 * @todo: Allow for locale-specific formatting.
	 *
	 * @since TBD
	 *
	 * @return string The value as a string.
	 */
	public function __toString() {
		return sprintf( '%F%%', $this->get_as_percent() );
	}

	/**
	 * Validate that the value is valid.
	 *
	 * @since TBD
	 *
	 * @throws InvalidArgumentException If the precision is greater than the max precision.
	 */
	public function validate_value(): void {
		if ( abs( $this->get() ) < 0.0001 ) {
			throw new InvalidArgumentException( 'Percent value cannot be smaller than 0.0001 (0.01%).' );
		}
	}
}
