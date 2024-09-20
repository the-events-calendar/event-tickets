<?php

namespace TEC\Tickets\Commerce;

use Tribe\Tickets\Test\Traits\With_Test_Orders;

class OrderTest extends \Codeception\TestCase\WPTestCase {

	use With_Test_Orders;

	/**
	 * @test
	 */
	public function it_should_get_events() {
		$this->prepare_test_data();

		foreach ( $this->orders as $order ) {
			$this->assertTrue( in_array( wp_list_pluck( tribe( Order::class )->get_events( $order ), 'ID' )['0'], $this->event_ids, true ) );
		}
	}

	/**
	 * @test
	 */
	public function it_should_get_value() {
		$this->prepare_test_data();
		foreach ( $this->orders as $order ) {
			$current_value  = tribe( Order::class )->get_value( $order );
			$original_value = tribe( Order::class )->get_value( $order, true );

			$this->assertSame( $current_value, $original_value );
			$this->assertEquals( '&#x24;1.00', $current_value );
		}
	}

	/**
	 * @test
	 */
	public function it_should_get_item_value() {
		$this->prepare_test_data();
		foreach ( $this->orders as $order ) {
			foreach ( $order->items as $item ) {
				$current_value  = tribe( Order::class )->get_item_value( $item );
				$original_value = tribe( Order::class )->get_item_value( $item, true );

				$this->assertSame( $current_value, $original_value );
				$this->assertEquals( '&#x24;1.00', $current_value );
			}
		}
	}
}
