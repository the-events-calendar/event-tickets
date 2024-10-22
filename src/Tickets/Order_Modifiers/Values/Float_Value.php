<?php
/**
 * Float Value.
 *
 * @since TBD
 */

declare( strict_types=1 );

namespace TEC\Tickets\Order_Modifiers\Values;

use InvalidArgumentException;
use TEC\Tickets\Order_Modifiers\Traits\Validate_Numeric;

/**
 * Class Float_Value
 *
 * @since TBD
 */
class Float_Value extends Base_Value {

	use Validate_Numeric;

	/**
	 * Get the value.
	 *
	 * @since TBD
	 *
	 * @return float
	 */
	public function get(): float {
		return $this->value;
	}

	/**
	 * Create a new instance from a numeric value.
	 *
	 * @param float|int|string $value The value to store. Can be a float, int, or numeric string.
	 *
	 * @return static
	 */
	public static function from_number( $value ): Float_Value {
		static::validate_numeric( $value );

		return new static( (float) $value );
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
		if ( ! is_float( $value ) ) {
			throw new InvalidArgumentException( 'Value must be a float.' );
		}
	}
}
