<?php

namespace TEC\Tickets\Commerce\CartV2;

use Codeception\TestCase\WPTestCase;

class CartTest extends WPTestCase {

	/**
	 * Data provider for different cart operations.
	 *
	 * @return \Generator
	 */
	public function cartOperationsProvider() {
		yield 'add single ticket' => [
			[ new Ticket_Item( 1, 2, 5000 ) ], // $50.00 ticket with quantity 2
			'expected_count' => 1,
			'expected_total' => 10000, // $100.00 (in cents)
		];

		yield 'add ticket and coupon' => [
			[ new Ticket_Item( 1, 1, 5000 ), new Coupon_Item( 2, 1, 10, 'percent' ) ],
			'expected_count' => 2,
			'expected_total' => 4500, // $45.00 (in cents)
		];

		yield 'add ticket and flat fee' => [
			[ new Ticket_Item( 1, 1, 5000 ), new Fee_Item( 2, 1, 500, 'flat' ) ],
			'expected_count' => 2,
			'expected_total' => 5500, // $55.00 (in cents)
		];

		yield 'add ticket and percentage fee' => [
			[ new Ticket_Item( 1, 1, 5000 ), new Fee_Item( 2, 1, 10, 'percent' ) ],
			'expected_count' => 2,
			'expected_total' => 5500, // $55.00 (in cents)
		];

		yield 'multiple percentage fees and coupons' => [
			[
				new Ticket_Item( 1, 1, 5000 ), // $50.00 ticket
				new Fee_Item( 2, 1, 10, 'percent' ), // 10% fee
				new Coupon_Item( 3, 1, 20, 'percent' ), // 20% discount
			],
			'expected_count' => 3,
			'expected_total' => 4500, // $45.00 (in cents)
		];

		yield 'negative flat coupon' => [
			[
				new Ticket_Item( 1, 1, 5000 ), // $50.00 ticket
				new Coupon_Item( 2, 1, -500, 'flat' ), // -$5.00 coupon (negative coupon value)
			],
			'expected_count' => 2,
			'expected_total' => 5500, // $55.00 (in cents)
		];

		yield 'coupon exceeds total' => [
			[
				new Ticket_Item( 1, 1, 1000 ), // $10.00 ticket
				new Coupon_Item( 2, 1, 1500, 'flat' ), // $15.00 coupon (exceeds ticket price)
			],
			'expected_count' => 2,
			'expected_total' => 0, // Total should not be negative
		];

		yield 'zero quantity and value items' => [
			[
				new Ticket_Item( 1, 0, 5000 ), // $50.00 ticket with zero quantity
				new Fee_Item( 2, 1, 0, 'flat' ), // Zero fee
			],
			'expected_count' => 2,
			'expected_total' => 0, // Total is zero
		];

		yield 'multiple tickets and fees' => [
			[
				new Ticket_Item( 1, 2, 5000 ), // $50.00 ticket, quantity 2
				new Ticket_Item( 2, 3, 7000 ), // $70.00 ticket, quantity 3
				new Ticket_Item( 3, 1, 10000 ), // $100.00 ticket, quantity 1
				new Fee_Item( 4, 1, 1500, 'flat' ), // $15.00 flat fee
				new Fee_Item( 5, 1, 5, 'percent' ), // 5% percentage fee
			],
			'expected_count' => 5,
			'expected_total' => 44550, // $445.50 (in cents)
		];

		yield 'multiple tickets, fees, and 50% off coupon' => [
			[
				new Ticket_Item( 1, 2, 5000 ), // $50.00 ticket, quantity 2
				new Ticket_Item( 2, 3, 7000 ), // $70.00 ticket, quantity 3
				new Ticket_Item( 3, 1, 10000 ), // $100.00 ticket, quantity 1
				new Fee_Item( 4, 1, 1500, 'flat' ), // $15.00 flat fee
				new Fee_Item( 5, 1, 5, 'percent' ), // 5% percentage fee
				new Coupon_Item( 6, 1, 50, 'percent' ), // 50% discount coupon
			],
			'expected_count' => 6,
			'expected_total' => 24050, // $240.50 (in cents)
		];
		yield 'high quantity tickets' => [
			[
				new Ticket_Item( 1, 1000, 5000 ), // $50.00 ticket, quantity 1,000
			],
			'expected_count' => 1,
			'expected_total' => 5000000, // $50,000.00 (in cents).
		];

		yield 'multiple percentage discounts' => [
			[
				new Ticket_Item( 1, 1, 10000 ), // $100.00 ticket
				new Coupon_Item( 2, 1, 10, 'percent' ), // 10% discount
				new Coupon_Item( 3, 1, 20, 'percent' ), // 20% discount
				new Coupon_Item( 4, 1, 50, 'percent' ), // 50% discount
			],
			'expected_count' => 4,
			'expected_total' => 2000, // $20.00 (in cents) after sequential discounts
		];

		yield 'free tickets with fees' => [
			[
				new Ticket_Item( 1, 2, 0 ), // Free ticket, quantity 2
				new Fee_Item( 2, 1, 1000, 'flat' ), // $10.00 flat fee
				new Fee_Item( 3, 1, 10, 'percent' ), // 10% fee
			],
			'expected_count' => 3,
			'expected_total' => 1000, // $10.00 (in cents)
		];

		yield 'multiple sequential percentage discounts' => [
			[
				new Ticket_Item( 1, 1, 10000 ), // $100.00 ticket
				new Coupon_Item( 2, 1, 50, 'percent' ), // 50% discount
				new Coupon_Item( 3, 1, 25, 'percent' ), // 25% discount
			],
			'expected_count' => 3,
			'expected_total' => 2500, // $25.00 (in cents) after sequential discounts
		];

		yield 'very small values with percentage fee' => [
			[
				new Ticket_Item( 1, 1, 1 ), // $0.01 ticket
				new Fee_Item( 2, 1, 10, 'percent' ), // 10% fee
			],
			'expected_count' => 2,
			'expected_total' => 1, // Total remains $0.01 due to rounding
		];
	}

	/**
	 * @test
	 * @dataProvider cartOperationsProvider
	 */
	public function it_can_perform_cart_operations( $items, $expected_count, $expected_total ) {
		$cart = new Cart();

		foreach ( $items as $item ) {
			$cart->add_item( $item );
		}

		$this->assertCount( $expected_count, $cart->get_items() );
		$total_value = $cart->get_total();
		$this->assertEquals( $expected_total, $total_value->get_integer() );
	}

	/**
	 * @test
	 */
	public function it_handles_adding_same_ticket_multiple_times() {
		$cart    = new Cart();
		$ticket1 = new Ticket_Item( 1, 1, 5000 ); // $50.00 ticket
		$ticket2 = new Ticket_Item( 1, 1, 5000 ); // Same ticket ID as above
		$ticket3 = new Ticket_Item( 1, 1, 5000 ); // Same ticket ID as above

		$cart->add_item( $ticket1 );
		$cart->add_item( $ticket2 );
		$cart->add_item( $ticket3 );

		$item_in_cart = $cart->get_item( $ticket1->get_id( true ) );

		// Ensure that quantity has been updated to 3
		$this->assertEquals( 3, $item_in_cart->get_quantity() );
	}

	/**
	 * @test
	 */
	public function it_can_handle_removing_items_from_the_cart() {
		$cart   = new Cart();
		$ticket = new Ticket_Item( 1, 1, 5000 ); // $50.00 ticket
		$cart->add_item( $ticket );

		$this->assertTrue( $cart->has_item( $ticket->get_id( true ) ) );

		$cart->remove_item( $ticket->get_id( true ) );

		$this->assertFalse( $cart->has_item( $ticket->get_id( true ) ) );
	}

	/**
	 * @test
	 */
	public function it_can_handle_adding_negative_value_item() {
		$cart         = new Cart();
		$negative_fee = new Fee_Item( 1, 1, -1000, 'flat' ); // -$10.00 fee

		$cart->add_item( $negative_fee );

		$total_value = $cart->get_total();
		// Ensure that negative value affects the total correctly
		$this->assertEquals( 0, $total_value->get_integer() ); // Total should not be negative
	}

	/**
	 * @test
	 */
	public function it_handles_large_cart_operations() {
		$this->markTestIncomplete( 'The loop tends to bog down testing. Look at alternative approaches.' );
		$cart = new Cart();

		// Add 10,000 tickets at $5.00 each
		for ( $i = 1; $i <= 10000; $i++ ) {
			$cart->add_item( new Ticket_Item( $i, 1, 500 ) );
		}

		$total_value = $cart->get_total();
		$this->assertEquals( 5000000, $total_value->get_integer() ); // $50,000.00 (in cents).
	}

	/**
	 * Data provider for overflow and large value scenarios.
	 *
	 * @return \Generator
	 */
	public function overflowOperationsProvider() {
		yield 'large quantity, high value' => [
			[
				new Ticket_Item( 1, 1000000, 1000000 ), // 1,000,000 tickets at $10,000.00 each
			],
			'expected_count' => 1,
			'expected_total' => 1000000000000, // $10,000,000,000.00 (in cents).
		];

		yield 'large quantity with percentage fee' => [
			[
				new Ticket_Item( 1, 500000, 20000 ), // 500,000 tickets at $200.00 each
				new Fee_Item( 2, 1, 10, 'percent' ), // 10% fee
			],
			'expected_count' => 2,
			'expected_total' => 11000000000, // $11,000,000.00 (in cents).
		];

		yield 'near integer overflow' => [
			[
				new Ticket_Item( 1, 1, PHP_INT_MAX / 2 ), // One ticket at half the maximum integer value
				new Ticket_Item( 2, 1, PHP_INT_MAX / 2 ), // Another ticket at half the maximum integer value
			],
			'expected_count' => 2,
			'expected_total' => ( 2 ** 31 ) - 1, // The maximum 32 bit integer.
		];

		yield 'large quantities with multiple fees' => [
			[
				new Ticket_Item( 1, 100000, 100000 ), // 100,000 tickets at $1,000.00 each
				new Fee_Item( 2, 1, 20, 'percent' ), // 20% fee
				new Fee_Item( 3, 1, 10, 'percent' ), // Another 10% fee
			],
			'expected_count' => 3,
			'expected_total' => 13000000000, // $13,200,000.00 (in cents).
		];
	}

	/**
	 * @test
	 * @dataProvider overflowOperationsProvider
	 */
	public function it_can_handle_large_value_and_overflow_cases( $items, $expected_count, $expected_total ) {
		$cart = new Cart();

		foreach ( $items as $item ) {
			$cart->add_item( $item );
		}

		$this->assertCount( $expected_count, $cart->get_items() );
		$total_value = $cart->get_total();
		$this->assertEquals( $expected_total, $total_value->get_integer() );
	}
}
