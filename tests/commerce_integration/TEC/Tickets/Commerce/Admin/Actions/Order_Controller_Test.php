<?php
namespace TEC\Tickets\Commerce\Admin\Actions;

use Codeception\TestCase\WPTestCase;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;

class Order_Controller_Test extends WPTestCase {
	use Ticket_Maker;
	use With_Uopz;

	/**
	 * @test
	 */
	public function test_is_valid_request() {
		$controller = tribe(Order_Controller::class);
		$this->assertFalse($controller->is_valid());

		// In admin and a valid nonce.
		$this->set_fn_return('is_admin', true);
		$_REQUEST[Order_Controller::NONCE_KEY] = wp_create_nonce(Order_Controller::NONCE_KEY);

		$this->assertTrue($controller->is_valid());
	}

	public function test_route_request() {

	}
}
