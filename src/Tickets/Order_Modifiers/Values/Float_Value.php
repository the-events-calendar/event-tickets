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
	public function get() {
		return $this->value;
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
