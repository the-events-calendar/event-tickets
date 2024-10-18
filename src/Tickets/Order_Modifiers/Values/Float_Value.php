<?php
/**
 * Float Value.
 *
 * @since TBD
 */

declare( strict_types=1 );

namespace TEC\Tickets\Order_Modifiers\Values;

use InvalidArgumentException;

/**
 * Class Float_Value
 *
 * @since TBD
 */
class Float_Value extends Base_Value {

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
		if ( ! is_numeric( $value ) ) {
			throw new InvalidArgumentException( 'Value must be a number.' );
		}

		if ( 'NAN' === (string) $value ) {
			throw new InvalidArgumentException( 'NAN is by definition not a number.' );
		}

		if ( 'INF' === (string) $value ) {
			throw new InvalidArgumentException( 'Infinity is too big for us to work with.' );
		}

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
