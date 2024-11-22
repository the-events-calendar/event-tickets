<?php

namespace TEC\Tickets\Commerce\Order_Modifiers;

use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Commerce\Order_Modifiers\API\Fees;
use Tribe\Tests\Traits\With_Uopz;

class Fees_Test extends Controller_Test_Case {
	use With_Uopz;

	protected string $controller_class = Fees::class;
}
