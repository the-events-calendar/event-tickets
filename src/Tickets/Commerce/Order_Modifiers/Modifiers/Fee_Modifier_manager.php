<?php
/**
 * Modifier Manager for handling operations and rendering related to Order Modifiers.
 *
 * This class serves as a context that interacts with different modifier strategies (such as Coupons or Booking Fees).
 * It handles the saving (insert/update) of modifiers and delegates rendering tasks to the appropriate strategy.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\Order_Modifiers\Modifiers
 */

namespace TEC\Tickets\Commerce\Order_Modifiers\Modifiers;

use TEC\Tickets\Commerce\Order_Modifiers\Modifiers\Fee;

class Fee_Modifier_Manager extends Modifier_Manager {

	public function __construct( Fee $fee ) {
		$this->strategy = $fee;
	}
}
