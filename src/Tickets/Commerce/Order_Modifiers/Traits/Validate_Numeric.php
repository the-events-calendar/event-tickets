<?php
/**
 * Validate Numeric trait.
 *
 * @since 5.18.0
 */

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Order_Modifiers\Traits;

use InvalidArgumentException;

/**
 * Trait Validate_Numeric
 *
 * @since 5.18.0
 */
trait Validate_Numeric {

	/**
	 * Validate that the value is a number, is not NAN, and is not INF.
	 *
	 * @since 5.18.0
	 *
	 * @param mixed $value The value to validate.
	 *
	 * @throws InvalidArgumentException If the value is not valid.
	 */
	protected static function validate_numeric( $value ) {
		if ( ! is_numeric( $value ) ) {
			throw new InvalidArgumentException( 'Value must be a number.' );
		}

		if ( 'NAN' === (string) $value ) {
			throw new InvalidArgumentException( 'NAN is by definition not a number.' );
		}

		if ( 'INF' === (string) $value ) {
			throw new InvalidArgumentException( 'Infinity is too big for us to work with.' );
		}
	}
}
