<?php

namespace TEC\Tickets\Commerce\Order_Modifiers;

use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Commerce\Order_Modifiers\API\Coupons;
use Tribe\Tests\Traits\With_Uopz;

class Coupons_Test extends Controller_Test_Case {
	use With_Uopz;

	protected string $controller_class = Coupons::class;
}
