<?php
/**
 * Positive Integer Value.
 *
 * @since TBD
 */

declare( strict_types=1 );

namespace TEC\Tickets\Order_Modifiers\Values;

use InvalidArgumentException;

/**
 * Class Positive_Integer_Value
 *
 * @since TBD
 */
class Positive_Integer_Value extends Integer_Value {

	/**
	 * Validate the value.
	 *
	 * @since TBD
	 *
	 * @param mixed $value The value to validate.
	 *
	 * @return void
	 * @throws InvalidArgumentException When the value is not an integer.
	 * @throws InvalidArgumentException When the value is not positive.
	 */
	protected function validate( $value ): void {
		parent::validate( $value );
		if ( abs( $value ) !== $value ) {
			throw new InvalidArgumentException( 'Value must be a positive integer.' );
		}
	}
}
