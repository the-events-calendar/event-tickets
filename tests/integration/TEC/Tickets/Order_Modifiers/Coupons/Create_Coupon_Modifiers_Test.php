<?php

namespace TEC\Tickets\Order_Modifiers\Coupons;

use Tribe\Tickets\Test\Partials\Order_Modifiers\Create_Order_Modifiers_Abstract;

class Create_Coupon_Modifiers_Test extends Create_Order_Modifiers_Abstract {

	/**
	 * The type of order modifier being tested (coupon).
	 *
	 * @var string
	 */
	protected string $modifier_type = 'coupon';
}
