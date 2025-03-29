<?php

namespace TEC\Tickets\Blocks;

use TEC\Common\Tests\Provider\Controller_Test_Case;
use Tribe\Tests\Traits\With_Uopz;

class ControllerTest extends Controller_Test_Case {

	use With_Uopz;

	protected string $controller_class = Controller::class;

	/**
	 * @test
	 */
	public function it_should_not_register_with_classy_active() {
		$this->set_fn_return( 'tec_using_classy_editor', true );
		$this->make_controller()->register();
		$this->assertFalse( $this->controller_class::is_registered(), 'Controller should not be registered with Classy active' );
	}
}
