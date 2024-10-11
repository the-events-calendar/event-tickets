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
		yield 'add one ticket with 2 quantity' => [
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
			'expected_total' => 5000, // $50.00 (in cents)
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
			'expected_count' => 1,
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
		$cart = new Cart();

		// Add 10,000 tickets at $5.00 each
		for ( $i = 1; $i <= 1000; $i++ ) {
			$cart->add_item( new Ticket_Item( $i, 1, 500 ) );
			$cart->add_item( new Ticket_Item( $i, 1, 500 ) );
			$cart->add_item( new Ticket_Item( $i, 1, 500 ) );
			$cart->add_item( new Ticket_Item( $i, 1, 500 ) );
			$cart->add_item( new Ticket_Item( $i, 1, 500 ) );
			$cart->add_item( new Ticket_Item( $i, 1, 500 ) );
			$cart->add_item( new Ticket_Item( $i, 1, 500 ) );
			$cart->add_item( new Ticket_Item( $i, 1, 500 ) );
			$cart->add_item( new Ticket_Item( $i, 1, 500 ) );
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
			'expected_total' => 13000000000, // $13,000,000.00 (in cents).
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

	/**
	 * Data provider for additional edge case scenarios.
	 *
	 * @return \Generator
	 */
	public function additionalEdgeCasesProvider() {
		yield 'multiple coupons that make the total zero or negative' => [
			[
				new Ticket_Item( 1, 1, 1000 ), // $10.00 ticket
				new Coupon_Item( 2, 1, 100, 'percent' ), // 100% off coupon
				new Coupon_Item( 3, 1, 500, 'flat' ), // Additional $5.00 coupon
			],
			'expected_count' => 3,
			'expected_total' => 0, // Total should not be negative
		];

		yield 'percentage-based fee on a very small value' => [
			[
				new Ticket_Item( 1, 1, 1 ), // $0.01 ticket
				new Fee_Item( 2, 1, 10, 'percent' ), // 10% fee
			],
			'expected_count' => 2,
			'expected_total' => 1, // Total remains $0.01 due to rounding
		];

		yield 'multiple free items with one paid item and a fee' => [
			[
				new Ticket_Item( 1, 5, 0 ), // Five free tickets
				new Ticket_Item( 2, 1, 1000 ), // $10.00 paid ticket
				new Fee_Item( 3, 1, 200, 'flat' ), // $2.00 flat fee
			],
			'expected_count' => 3,
			'expected_total' => 1200, // $12.00 (in cents)
		];

		yield 'high quantity with small unit price' => [
			[
				new Ticket_Item( 1, 1000000, 1 ), // 1,000,000 tickets at $0.01 each
			],
			'expected_count' => 1,
			'expected_total' => 1000000, // $10,000.00 (in cents)
		];

		yield 'invalid coupon type' => [
			[
				new Ticket_Item( 1, 1, 5000 ), // $50.00 ticket
				new Coupon_Item( 2, 1, 5000, 'invalid_type' ), // Invalid coupon type
			],
			'expected_count' => 2,
			'expected_total' => 5000, // Coupon should be ignored
		];

		yield 'large values with fees and coupons' => [
			[
				new Ticket_Item( 1, 1, 1000000000 ), // $10,000,000.00 ticket
				new Fee_Item( 2, 1, 15, 'percent' ), // 15% fee
				new Coupon_Item( 3, 1, 10, 'percent' ), // 10% coupon
			],
			'expected_count' => 3,
			'expected_total' => 1050000000, // $10,500,000.00 (in cents)
		];
	}

	/**
	 * @test
	 * @dataProvider additionalEdgeCasesProvider
	 */
	public function it_handles_additional_edge_cases( $items, $expected_count, $expected_total ) {
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
	public function it_handles_invalid_quantities() {
		$cart           = new Cart();
		$invalid_ticket = new Ticket_Item( 1, -5, 5000 ); // Negative quantity should not be allowed

		$cart->add_item( $invalid_ticket );

		// Expect no items in the cart
		$this->assertCount( 0, $cart->get_items() );
		$total_value = $cart->get_total();
		$this->assertEquals( 0, $total_value->get_integer() );
	}

	/**
	 * @test
	 */
	public function it_limits_high_quantity_items() {
		$cart               = new Cart();
		$high_quantity_item = new Ticket_Item( 1, PHP_INT_MAX, 1 ); // Excessive quantity should be capped

		$cart->add_item( $high_quantity_item );

		// Check that the quantity is capped
		$item_in_cart = $cart->get_item( $high_quantity_item->get_id( true ) );
		$this->assertLessThanOrEqual( 1000000, $item_in_cart->get_quantity(), 'Item quantity should be capped' );
	}

	/**
	 * @test
	 */
	public function it_handles_float_to_integer_conversion() {
		$cart                     = new Cart();
		$large_value_item         = new Ticket_Item( 1, 1, PHP_INT_MAX / 2 );
		$another_large_value_item = new Ticket_Item( 2, 1, PHP_INT_MAX / 2 );

		$cart->add_item( $large_value_item );
		$cart->add_item( $another_large_value_item );

		$total_value = $cart->get_total();

		// Should be capped at the max value for a 32-bit integer
		$this->assertEquals( ( 2 ** 31 ) - 1, $total_value->get_integer() );
	}

	/**
	 * @test
	 */
	public function it_handles_decimal_quantities() {
		$cart                  = new Cart();
		$decimal_quantity_item = new Ticket_Item( 1, 1.5, 5000 ); // 1.5 tickets at $50.00 each

		$cart->add_item( $decimal_quantity_item );

		// Expect it to round down to the nearest integer (1 in this case)
		$this->assertEquals( 1, $cart->get_item( $decimal_quantity_item->get_id( true ) )->get_quantity() );
	}

	/**
	 * @test
	 */
	public function it_handles_removing_all_items_after_coupon() {
		$cart   = new Cart();
		$ticket = new Ticket_Item( 1, 1, 1000 ); // $10.00 ticket
		$coupon = new Coupon_Item( 2, 1, 10, 'percent' ); // 10% off coupon

		$cart->add_item( $ticket );
		$cart->add_item( $coupon );

		// Remove the ticket and ensure the total is zero
		$cart->remove_item( $ticket->get_id( true ) );
		$this->assertEquals( 0, $cart->get_total()->get_integer() );
	}

	/**
	 * @test
	 */
	public function it_handles_high_precision_rounding() {
		$cart             = new Cart();
		$small_value_item = new Ticket_Item( 1, 1, 0.5 ); // $0.005 ticket

		$cart->add_item( $small_value_item );

		// Ensure rounding behaves correctly, expecting 0 due to rounding down
		$this->assertEquals( 0, $cart->get_total()->get_integer() );
	}

	/**
	 * @test
	 */
	public function it_handles_negative_quantity_adjustment() {
		$cart            = new Cart();
		$ticket          = new Ticket_Item( 1, 5, 5000 ); // 5 tickets at $50.00 each
		$negative_ticket = new Ticket_Item( 1, -5, 5000 ); // -5 tickets adjustment

		$cart->add_item( $ticket );
		$cart->add_item( $negative_ticket );

		// Expect the cart to be empty as quantities zero out
		$this->assertCount( 0, $cart->get_items() );
	}

	/**
	 * @test
	 */
	public function it_prevents_negative_discounts_exceeding_item_value() {
		$cart                  = new Cart();
		$ticket                = new Ticket_Item( 1, 1, 1000 ); // $10.00 ticket
		$large_negative_coupon = new Coupon_Item( 2, 1, 1500, 'flat' ); // -$15.00 coupon

		$cart->add_item( $ticket );
		$cart->add_item( $large_negative_coupon );

		// The total should not be less than zero
		$this->assertEquals( 0, $cart->get_total()->get_integer() );
	}

	/**
	 * @test
	 */
	public function it_ignores_invalid_coupon_types() {
		// @todo - Enum sub types?
		$this->markTestSkipped('Coupon sub types default to flat');
		$cart           = new Cart();
		$ticket         = new Ticket_Item( 1, 1, 1000 ); // $10.00 ticket
		$invalid_coupon = new Coupon_Item( 2, 1, 500, 'nonexistent_type' ); // Invalid coupon type

		$cart->add_item( $ticket );
		$cart->add_item( $invalid_coupon );

		// The total should be unaffected by the invalid coupon
		$this->assertEquals( 1000, $cart->get_total()->get_integer() );
	}

	/**
	 * @test
	 */
	public function it_prevents_exploiting_negative_fees_to_increase_cart_total() {
		$cart         = new Cart();
		$ticket       = new Ticket_Item( 1, 1, 1000 ); // $10.00 ticket
		$negative_fee = new Fee_Item( 2, 1, -500, 'flat' ); // -$5.00 fee
		$positive_fee = new Fee_Item( 3, 1, 2000, 'flat' ); // $20.00 fee

		$cart->add_item( $ticket );
		$cart->add_item( $negative_fee );
		$cart->add_item( $positive_fee );

		// The total should be $30.00 (1000 - 0 + 2000), not more than that
		$this->assertEquals( 3000, $cart->get_total()->get_integer() );
	}

	/**
	 * @test
	 */
	public function it_handles_multiple_discounts_without_going_below_zero() {
		$cart    = new Cart();
		$ticket  = new Ticket_Item( 1, 1, 1000 ); // $10.00 ticket
		$coupon1 = new Coupon_Item( 2, 1, 50, 'percent' ); // 50% discount
		$coupon2 = new Coupon_Item( 3, 1, 75, 'percent' ); // 75% discount

		$cart->add_item( $ticket );
		$cart->add_item( $coupon1 );
		$cart->add_item( $coupon2 );

		// The total should not go below zero even with aggressive discounts
		$this->assertEquals( 0, $cart->get_total()->get_integer() );
	}

	/**
	 * @test
	 */
	public function it_limits_high_value_discount_exploit_attempts() {
		$cart              = new Cart();
		$ticket            = new Ticket_Item( 1, 1, 1000000 ); // $10,000.00 ticket
		$high_value_coupon = new Coupon_Item( 2, 1, 2000000, 'flat' ); // $20,000.00 coupon

		$cart->add_item( $ticket );
		$cart->add_item( $high_value_coupon );

		// The total should not be negative, even if the coupon exceeds the item value
		$this->assertEquals( 0, $cart->get_total()->get_integer() );
	}

	/**
	 * @test
	 */
	public function it_handles_edge_case_small_values_securely() {
		$cart             = new Cart();
		$small_value_item = new Ticket_Item( 1, 1, 1 ); // $0.01 ticket
		$negative_fee     = new Fee_Item( 2, 1, 2, 'flat' ); // $0.02 fee

		$cart->add_item( $small_value_item );
		$cart->add_item( $negative_fee );

		// Total should not be allowed to go negative
		$this->assertEquals( 3, $cart->get_total()->get_integer() );
	}

	/**
	 * @test
	 */
	public function it_ignores_overly_high_percentage_discounts() {
		$cart                      = new Cart();
		$ticket                    = new Ticket_Item( 1, 1, 1000 ); // $10.00 ticket
		$over_100_percent_discount = new Coupon_Item( 2, 1, 150, 'percent' ); // 150% discount

		$cart->add_item( $ticket );
		$cart->add_item( $over_100_percent_discount );

		// The total should be zero, not negative, despite the excessive discount
		$this->assertEquals( 0, $cart->get_total()->get_integer() );
	}

	/**
	 * @test
	 */
	public function it_handles_combination_of_fees_and_negative_values_without_exploit() {
		$cart            = new Cart();
		$ticket          = new Ticket_Item( 1, 1, 2000 ); // $20.00 ticket
		$negative_coupon = new Coupon_Item( 2, 1, -500, 'flat' ); // -$5.00 coupon
		$flat_fee        = new Fee_Item( 3, 1, 1000, 'flat' ); // $10.00 fee

		$cart->add_item( $ticket );
		$cart->add_item( $negative_coupon );
		$cart->add_item( $flat_fee );

		// Total should be $30.00 (2000 - 0 + 3000)
		$this->assertEquals( 3000, $cart->get_total()->get_integer() );
	}

	/**
	 * @test
	 */
	public function it_prevents_multiple_negative_discounts_from_exceeding_value() {
		$cart             = new Cart();
		$ticket           = new Ticket_Item( 1, 1, 1000 ); // $10.00 ticket
		$negative_coupon1 = new Coupon_Item( 2, 1, -500, 'flat' ); // -$5.00 coupon
		$negative_coupon2 = new Coupon_Item( 3, 1, -500, 'flat' ); // Another -$5.00 coupon
		$negative_coupon3 = new Coupon_Item( 3, 1, -500, 'flat' ); // Another -$5.00 coupon

		$cart->add_item( $ticket );
		$cart->add_item( $negative_coupon1 );
		$cart->add_item( $negative_coupon2 );
		$cart->add_item( $negative_coupon3 );

		// The total should still be $10.00 since negative discounts should not increase the total
		$this->assertEquals( 1000, $cart->get_total()->get_integer() );
	}
}
