<?php
/**
 * Base Value for implementing Value_Interface.
 *
 * @since TBD
 */

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Order_Modifiers\Values;

use InvalidArgumentException;
use TEC\Tickets\Commerce\Order_Modifiers\Traits\Stringify;

/**
 * Class Base_Value
 *
 * @since TBD
 */
abstract class Base_Value implements Value_Interface {

	use Stringify;

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
