<?php

namespace TEC\Tickets\Commerce\Order_Modifiers;

use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Commerce\Order_Modifiers\Modifiers\Coupon;
use TEC\Tickets\Commerce\Order_Modifiers\Modifiers\Fee;
use InvalidArgumentException;

class Controller_Test extends Controller_Test_Case {
	protected string $controller_class = Controller::class;

	/**
	 * @test
	 */
	public function it_should_locate_modifiers() {
		$controller = $this->make_controller();
		$coupon = $controller->get_modifier( 'coupon' );
		$this->assertInstanceOf( Coupon::class, $coupon );

		$fee = $controller->get_modifier( 'fee' );
		$this->assertInstanceOf( Fee::class, $fee );
	}

	/**
	 * @test
	 */
	public function it_should_throw_exception_on_invalid_modifiers() {
		$this->expectException( InvalidArgumentException::class );
		$controller = $this->make_controller();
		$controller->get_modifier( 'does_not_exists' );
	}

	/**
	 * @test
	 */
	public function it_should_locate_modifier_names() {
		$this->assertEquals( 'Coupons', Controller::get_modifier_display_name( 'coupon' ) );
		$this->assertEquals( 'Fees', Controller::get_modifier_display_name( 'fee' ) );
	}
}
