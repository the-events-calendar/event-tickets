<?php
/**
 * Integer Value.
 *
 * @since TBD
 */

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Order_Modifiers\Values;

use InvalidArgumentException;
use TEC\Tickets\Commerce\Order_Modifiers\Traits\Validate_Numeric;

/**
 * Class Integer_Value
 *
 * @since TBD
 */
class Integer_Value extends Base_Value {

	use Validate_Numeric;

	/**
	 * Get the value.
	 *
	 * @since TBD
	 *
	 * @return int
	 */
	public function get(): int {
		return $this->value;
	}

	/**
	 * Create a new instance from a numeric value.
	 *
	 * This will convert the value to an integer.
	 *
	 * @since TBD
	 *
	 * @param float|int|string $value The value to store. Can be a float, int, or numeric string.
	 *
	 * @return static
	 * @throws InvalidArgumentException When the value is not numeric.
	 */
	public static function from_number( $value ): Integer_Value {
		static::validate_numeric( $value );

		return new static( (int) $value );
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
		if ( ! is_int( $value ) ) {
			throw new InvalidArgumentException( 'Value must be an integer.' );
		}
	}
}
