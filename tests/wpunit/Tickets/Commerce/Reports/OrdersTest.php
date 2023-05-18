<?php

namespace Tribe\Tickets\Commerce\Reports;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Reports\Orders;

class OrdersTest extends WPTestCase {
	/**
	 * Instance of the class being tested.
	 *
	 * @var Orders
	 */
	protected $orders;

	/**
	 * Setup function for each test in this group.
	 */
	public function setUp(): void {
		parent::setUp();

		$commerceMock = $this->makeEmpty(Orders::class);
		$this->orders = new Orders($commerceMock);
	}

	/**
	 * @test
	 * Test that the Orders instance is an instance of the Orders class.
	 */
	public function ordersInstance() {
		$this->assertInstanceOf(Orders::class, $this->orders);
	}

	/**
	 * @test
	 * Test the get_title() method.
	 */
	public function get_titleReturnsString() {
		$result = $this->orders->get_title(1);

		$this->assertIsString($result);
	}
}