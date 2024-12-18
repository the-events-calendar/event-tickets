<?php
/**
 * Positive Integer Value.
 *
 * @since 5.18.0
 */

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Order_Modifiers\Values;

use InvalidArgumentException;

/**
 * Class Positive_Integer_Value
 *
 * @since 5.18.0
 */
class Positive_Integer_Value extends Integer_Value {

	/**
	 * Validate the value.
	 *
	 * @since 5.18.0
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
