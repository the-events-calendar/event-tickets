<?php
/**
 * Precision Value
 *
 * @since TBD
 */

declare( strict_types=1 );

namespace TEC\Tickets\Order_Modifiers\Values;

use InvalidArgumentException;
use TEC\Tickets\Order_Modifiers\Values\Positive_Integer_Value as Positive_Int;

/**
 * Class Precision_Value.
 *
 * This class is used to store a value with a specific precision (how many decimal places).
 * It will store the value as an integer to prevent floating point errors, and when
 * the value is retrieved, it will be converted back to a float.
 *
 * @since TBD
 */
class Precision_Value extends Base_Value {

	/**
	 * The precision (how many decimal places).
	 *
	 * @var int
	 */
	protected $precision;

	/**
	 * Currency_Value constructor.
	 *
	 * @since TBD
	 *
	 * @param float         $value     The value to store.
	 * @param ?Positive_Int $precision The precision (how many decimal places).
	 */
	public function __construct( $value, ?Positive_Int $precision = null ) {
		$this->validate( $value );
		$this->precision = $precision ?? new Positive_Int( 2 );
		$this->value     = $this->convert_value_to_integer( (float) $value );
	}

	/**
	 * Convert the value to an integer.
	 *
	 * @param float $value The value to convert.
	 *
	 * @return int
	 */
	protected function convert_value_to_integer( $value ): int {
		return (int) round( $value * ( 10 ** $this->precision->get() ) );
	}

	/**
	 * Convert the value to a float.
	 *
	 * @param int $value The value to convert.
	 *
	 * @return float
	 */
	protected function convert_value_to_float( $value ): float {
		return (float) ( $value / ( 10 ** $this->precision->get() ) );
	}

	/**
	 * Get the value.
	 *
	 * @since TBD
	 *
	 * @return float
	 */
	public function get(): float {
		return $this->convert_value_to_float( $this->value );
	}

	/**
	 * Get the precision.
	 *
	 * This returns a clone of the precision value to prevent mutation.
	 *
	 * @since TBD
	 *
	 * @return Positive_Int The precision.
	 */
	public function get_precision(): Positive_Int {
		return clone $this->precision;
	}

	/**
	 * Convert this object to an object with a new precision level.
	 *
	 * @since TBD
	 *
	 * @param Positive_Int $precision The new precision level.
	 *
	 * @return static Will return the same instance if the precision is the same, or
	 *                a new instance when the precision has changed.
	 */
	public function convert_to_precision( Positive_Int $precision ) {
		if ( $this->precision->get() === $precision->get() ) {
			return $this;
		}

		return new static( $this->get(), $precision );
	}

	/**
	 * Validate that the value is valid.
	 *
	 * @since TBD
	 *
	 * @param mixed $value The value to validate.
	 *
	 * @return void
	 * @throws InvalidArgumentException When the value is not valid.
	 */
	protected function validate( $value ): void {
		if ( ! is_numeric( $value ) ) {
			throw new InvalidArgumentException( 'Value must be numeric.' );
		}
	}
}
