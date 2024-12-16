<?php
/**
 * Float Value.
 *
 * @since 5.18.0
 */

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Order_Modifiers\Values;

use InvalidArgumentException;
use TEC\Tickets\Commerce\Order_Modifiers\Traits\Validate_Numeric;

/**
 * Class Float_Value
 *
 * @since 5.18.0
 */
class Float_Value extends Base_Value {

	use Validate_Numeric;

	/**
	 * Get the value.
	 *
	 * @since 5.18.0
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
	 * @throws InvalidArgumentException When the value is not numeric.
	 */
	public static function from_number( $value ): Float_Value {
		static::validate_numeric( $value );

		return new static( (float) $value );
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
		if ( ! is_float( $value ) ) {
			throw new InvalidArgumentException( 'Value must be a float.' );
		}
	}
}
