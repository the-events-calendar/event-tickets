<?php
/**
 * Fee_Modifier_Manager class
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\Order_Modifiers\Modifiers
 */

namespace TEC\Tickets\Commerce\Order_Modifiers\Modifiers;

use TEC\Tickets\Commerce\Order_Modifiers\Modifiers\Fee;

/**
 * Fee_Modifier_Manager class
 *
 * @since TBD
 */
class Fee_Modifier_Manager extends Modifier_Manager {

	/**
	 * Fee_Modifier_Manager constructor.
	 *
	 * @param Fee $fee The fee object.
	 */
	public function __construct( Fee $fee ) {
		$this->strategy = $fee;
	}
}
