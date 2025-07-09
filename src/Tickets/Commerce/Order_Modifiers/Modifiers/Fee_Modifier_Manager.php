<?php
/**
 * Fee_Modifier_Manager class
 *
 * @since 5.18.0
 *
 * @package TEC\Tickets\Commerce\Order_Modifiers\Modifiers
 */

namespace TEC\Tickets\Commerce\Order_Modifiers\Modifiers;

/**
 * Fee_Modifier_Manager class
 *
 * @since 5.18.0
 */
class Fee_Modifier_Manager extends Modifier_Manager {

	/**
	 * Fee_Modifier_Manager constructor.
	 *
	 * @param Fee $fee The fee object.
	 */
	public function __construct( Fee $fee ) {
		parent::__construct( $fee );
	}
}
