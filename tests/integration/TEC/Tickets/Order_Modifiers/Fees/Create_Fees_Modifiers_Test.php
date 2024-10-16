<?php

namespace TEC\Tickets\Order_Modifiers\Fees;

use Tribe\Tickets\Test\Partials\Order_Modifiers\Create_Order_Modifiers_Abstract;

class Create_Fees_Modifiers_Test extends Create_Order_Modifiers_Abstract {

	/**
	 * The type of order modifier being tested (fee).
	 *
	 * @var string
	 */
	protected string $modifier_type = 'fee';

}
