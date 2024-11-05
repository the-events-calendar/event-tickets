<?php
/**
 * Stringify trait.
 *
 * @since TBD
 */

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Order_Modifiers\Traits;

/**
 * Trait Stringify
 *
 * @since TBD
 */
trait Stringify {

	/**
	 * Get the value as a string.
	 *
	 * @since TBD
	 *
	 * @return string The value as a string.
	 */
	public function __toString() {
		return (string) $this->get();
	}

	/**
	 * Get the value.
	 *
	 * @since TBD
	 *
	 * @return mixed The value.
	 */
	abstract public function get();
}
