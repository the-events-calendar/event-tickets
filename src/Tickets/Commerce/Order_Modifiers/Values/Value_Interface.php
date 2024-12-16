<?php
/**
 * Interface Value_Interface
 *
 * @since 5.18.0
 */

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Order_Modifiers\Values;

/**
 * Interface Value_Interface
 *
 * @since 5.18.0
 */
interface Value_Interface {

	/**
	 * Get the value.
	 *
	 * @since 5.18.0
	 *
	 * @return mixed The value.
	 */
	public function get();
}
