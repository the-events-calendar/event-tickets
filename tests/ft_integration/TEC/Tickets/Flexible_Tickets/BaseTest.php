<?php

namespace Tribe\src;

use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series_Post_Type;
use TEC\Tickets\Flexible_Tickets\Base;

class BaseTest extends Controller_Test_Case {
	protected string $controller_class = Base::class;
}
