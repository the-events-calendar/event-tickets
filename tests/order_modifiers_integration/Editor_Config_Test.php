<?php

namespace TEC\Tickets\Commerce\Order_Modifiers;

use TEC\Common\Tests\Provider\Controller_Test_Case;
use Tribe\Tests\Traits\With_Uopz;

class Editor_Config_Test extends Controller_Test_Case {
	use With_Uopz;

	protected string $controller_class = Editor_Config::class;
}
