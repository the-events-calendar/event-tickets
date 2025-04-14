<?php
/**
 * Precision Value
 *
 * @since 5.18.0
 */

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Values;

use InvalidArgumentException;
use TEC\Tickets\Commerce\Values\Positive_Integer_Value as Positive_Int;

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
	 * The default precision.
	 *
	 * @var int
	 */
	protected static int $default_precision = 2;

	/**
	 * The maximum precision allowed.
	 *
	 * @var int
	 */
	protected static int $max_precision = 6;

	/**
	 * Currency_Value constructor.
	 *
	 * @since 5.18.0
	 *
	 * @param float|int|string $value     The value to store. Can be a float, int, or numeric string.
	 * @param ?int             $precision The precision (how many decimal places).
	 *
	 * @throws InvalidArgumentException When the value is not numeric, or the precision is not a positive integer.
	 */
	public function __construct( $value, ?int $precision = null ) {
		$value           = Float_Value::from_number( $value )->get();
		$this->precision = new Positive_Int( $precision ?? self::$default_precision );

		parent::__construct( $this->convert_value_to_integer( $value ) );
	}

	/**
	 * Convert the value to an integer.
	 *
	 * @since 5.18.0
	 *
	 * @param float $value The value to convert.
	 *
	 * @return int The value as an integer.
	 */
	protected function convert_value_to_integer( float $value ): int {
		return (int) round( $value * ( 10 ** $this->precision->get() ) );
	}

	/**
	 * Convert the value to a float.
	 *
	 * @since 5.18.0
	 *
	 * @param int $value The value to convert.
	 *
	 * @return float The value as a float.
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
	 * Get the value as an integer.
	 *
	 * Note that this is the RAW integer value. For example, a value of 1.23 with a precision of 2
	 * would return 123.
	 *
	 * @since 5.21.0
	 *
	 * @param ?int $precision The precision to use. Passing null will use the default precision.
	 *
	 * @return int The value as an integer.
	 */
	public function get_as_integer( ?int $precision = null ): int {
		// If the precision is not set, use the precision already set in this object.
		if ( null === $precision ) {
			return $this->value;
		}

		// Set up a new object with the desired precision.
		return $this->convert_to_precision( $precision )->value;
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
	 * Multiply this value by another value.
	 *
	 * This will multiply the values together and return a new value object with the product.
	 * The precision of the new value will be determined by the precision of THIS object.
	 *
	 * @since 5.21.0
	 *
	 * @param Precision_Value $value The value to multiply by.
	 *
	 * @return static The new value object.
	 */
	public function multiply( Precision_Value $value ): Precision_Value {
		// Get the common precision level.
		$common_precision  = max( $this->get_precision(), $value->get_precision() );

		// Convert both numbers to the common precision level.
		$current_value = $this->convert_to_precision( $common_precision );
		$value         = $value->convert_to_precision( $common_precision );

		// The calculation precision level will be the common precision times 2.
		$calculation_precision = $common_precision * 2;

		// Multiply the values together.
		$new_value = $current_value->value * $value->value;

		return new static(
			(float) ( $new_value / ( 10 ** $calculation_precision ) ),
			$this->get_precision()
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
	 * Invert the sign of the value.
	 *
	 * Converts a negative value to a positive value, and vice versa.
	 *
	 * @since 5.21.0
	 *
	 * @return Precision_Value The new value object.
	 */
	public function invert_sign(): Precision_Value {
		return $this->multiply_by_integer( new Integer_Value( -1 ) );
	}

	/**
	 * Get the value as a string.
	 *
	 * @since 5.21.0
	 *
	 * @return string The value as a string.
	 */
	public function __toString() {
		$precision = $this->precision->get();

		return sprintf( "%02.{$precision}F", $this->get() );
	}

	/**
	 * Validate that the precision is valid.
	 *
	 * @since 5.18.0
	 * @since 5.21.0 Made the method static.
	 *
	 * @throws InvalidArgumentException If the precision is greater than the max precision.
	 */
	protected static function validate_precision( int $precision ) {
		if ( $precision > self::$max_precision ) {
			throw new InvalidArgumentException( sprintf( 'Precision cannot be greater than %d', self::$max_precision ) );
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
		$this->validate_precision( $this->precision->get() );
	}

	/**
	 * Set the default precision.
	 *
	 * @since 5.21.0
	 *
	 * @param int $precision The default precision.
	 */
	public static function set_default_precision( int $precision ) {
		$object = new Positive_Int( $precision );
		self::validate_precision( $object->get() );
		self::$default_precision = $object->get();
	}
}
