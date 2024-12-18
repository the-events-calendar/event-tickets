<?php
/**
 * Precision Value
 *
 * @since 5.18.0
 */

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Order_Modifiers\Values;

use InvalidArgumentException;
use TEC\Tickets\Commerce\Order_Modifiers\Traits\Stringify;
use TEC\Tickets\Commerce\Order_Modifiers\Values\Positive_Integer_Value as Positive_Int;

/**
 * Class Precision_Value.
 *
 * This class is used to store a value with a specific precision (how many decimal places).
 * It will store the value as an integer to prevent floating point errors, and when
 * the value is retrieved, it will be converted back to a float.
 *
 * @since 5.18.0
 */
class Precision_Value extends Base_Value {
	/**
	 * The precision (how many decimal places).
	 *
	 * @var Positive_Int
	 */
	protected Positive_Int $precision;

	/**
	 * The maximum precision allowed.
	 *
	 * @var int
	 */
	protected int $max_precision = 6;

	/**
	 * Currency_Value constructor.
	 *
	 * @since 5.18.0
	 *
	 * @param float|int|string $value     The value to store. Can be a float, int, or numeric string.
	 * @param ?int             $precision The precision (how many decimal places).
	 */
	public function __construct( $value, ?int $precision = null ) {
		$value           = Float_Value::from_number( $value )->get();
		$this->precision = new Positive_Int( $precision ?? 2 );

		$this->validate_precision();

		$this->value = $this->convert_value_to_integer( $value );
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
	protected function convert_value_to_float( int $value ): float {
		return (float) ( $value / ( 10 ** $this->precision->get() ) );
	}

	/**
	 * Get the value.
	 *
	 * @since 5.18.0
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
	 * @since 5.18.0
	 *
	 * @return int The precision.
	 */
	public function get_precision(): int {
		return $this->precision->get();
	}

	/**
	 * Add a value to this value.
	 *
	 * @since 5.18.0
	 *
	 * @param Precision_Value $value The value to add.
	 *
	 * @return static The new value object
	 */
	public function add( Precision_Value $value ) {
		$current_value = $this;
		$precision     = $this->get_precision();

		if ( $precision !== $value->get_precision() ) {
			$precision     = max( $precision, $value->get_precision() );
			$current_value = $this->convert_to_precision( $precision );
			$value         = $value->convert_to_precision( $precision );
		}

		$new_value = $current_value->value + $value->value;

		return new static(
			(float) ( $new_value / ( 10 ** $precision ) ),
			$precision
		);
	}

	/**
	 * Subtract a value from this value.
	 *
	 * @since 5.18.0
	 *
	 * @param Precision_Value $value The value to subtract.
	 *
	 * @return static The new value object
	 */
	public function subtract( Precision_Value $value ) {
		$negative_value = new Precision_Value(
			$value->get() * -1,
			$value->get_precision()
		);

		return $this->add( $negative_value );
	}

	/**
	 * Add multiple values together.
	 *
	 * @since 5.18.0
	 *
	 * @param Precision_Value ...$values The values to add.
	 *
	 * @return Precision_Value The sum of the values.
	 */
	public static function sum( Precision_Value ...$values ): Precision_Value {
		$sum = new static( 0 );

		foreach ( $values as $value ) {
			$sum = $sum->add( $value );
		}

		return $sum;
	}

	/**
	 * Multiply this value by another value.
	 *
	 * @param Integer_Value $value The value to multiply by.
	 *
	 * @return Precision_Value The new value object.
	 */
	public function multiply_by_integer( Integer_Value $value ): Precision_Value {
		$new_value = $this->value * $value->get();

		return new static(
			(float) ( $new_value / ( 10 ** $this->precision->get() ) ),
			$this->precision->get()
		);
	}

	/**
	 * Convert this object to an object with a new precision level.
	 *
	 * @since 5.18.0
	 *
	 * @param int $precision The new precision level.
	 *
	 * @return static Will return the same instance if the precision is the same, or
	 *                a new instance when the precision has changed.
	 */
	public function convert_to_precision( int $precision ) {
		if ( $this->precision->get() === $precision ) {
			return $this;
		}

		return new static( $this->get(), $precision );
	}

	/**
	 * Validate that the precision is valid.
	 *
	 * @since 5.18.0
	 *
	 * @throws InvalidArgumentException If the precision is greater than the max precision.
	 */
	protected function validate_precision() {
		if ( $this->precision->get() > $this->max_precision ) {
			throw new InvalidArgumentException( sprintf( 'Precision cannot be greater than %d', $this->max_precision ) );
		}
	}

	/**
	 * Validate that the value is valid.
	 *
	 * @since 5.18.0
	 *
	 * @param mixed $value The value to validate.
	 *
	 * @return void
	 * @throws InvalidArgumentException When the value is not valid.
	 */
	protected function validate( $value ): void {
		$this->validate_precision();
	}
}
