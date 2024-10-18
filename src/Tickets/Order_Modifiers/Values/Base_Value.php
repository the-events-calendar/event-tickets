<?php
/**
 * Base Value for implementing Value_Interface.
 *
 * @since TBD
 */

declare( strict_types=1 );

namespace TEC\Tickets\Order_Modifiers\Values;

use InvalidArgumentException;

/**
 * Class Base_Value
 *
 * @since TBD
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
	 * @since TBD
	 *
	 * @param mixed $value The value to store.
	 */
	public function __construct( $value ) {
		$this->validate( $value );
		$this->value = $value;
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
	abstract protected function validate( $value ): void;
}