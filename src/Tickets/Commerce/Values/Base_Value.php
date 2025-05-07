<?php
/**
 * Base Value for implementing Value_Interface.
 *
 * @since 5.18.0
 */

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Values;

use InvalidArgumentException;

/**
 * Class Base_Value
 *
 * @since 5.18.0
 */
abstract class Base_Value implements Value_Interface {

	/**
	 * The value.
	 *
	 * @var mixed
	 */
	protected $value;

	/**
	 * Base_Value constructor.
	 *
	 * @since 5.18.0
	 *
	 * @param mixed $value The value to store.
	 *
	 * @throws InvalidArgumentException When the value is not valid. See the validate method.
	 */
	public function __construct( $value ) {
		$this->validate( $value );
		$this->value = $value;
	}

	/**
	 * Get the value as a string.
	 *
	 * @since 5.18.0
	 *
	 * @return string The value as a string.
	 */
	public function __toString() {
		return (string) $this->get();
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
	protected function validate( $value ): void {}
}
