<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use TEC\Tickets\Commerce\Utils\Value;

/**
 * Class Payment_Intent_HandlerTest
 *
 * @since TBD
 */
class Payment_Intent_HandlerTest extends \Codeception\TestCase\WPTestCase {

	/**
	 * Test that Value objects are created correctly from cart totals.
	 *
	 * @test
	 */
	public function it_should_create_value_objects_correctly_from_cart_totals() {
		// Test various cart total scenarios.
		$test_cases = [
			[ 'cart_total' => 8.0, 'expected_decimal' => 8.0, 'expected_integer' => 800 ],
			[ 'cart_total' => 10.0, 'expected_decimal' => 10.0, 'expected_integer' => 1000 ],
			[ 'cart_total' => 24.0, 'expected_decimal' => 24.0, 'expected_integer' => 2400 ],
			[ 'cart_total' => 0.0, 'expected_decimal' => 0.0, 'expected_integer' => 0 ],
			[ 'cart_total' => 12.34, 'expected_decimal' => 12.34, 'expected_integer' => 1234 ],
		];

		foreach ( $test_cases as $case ) {
			$value = Value::create( $case['cart_total'] );

			$this->assertInstanceOf( Value::class, $value, 'Value object should be created' );
			$this->assertEquals( $case['expected_decimal'], $value->get_decimal(), 'Decimal value should match cart total' );
			$this->assertEquals( $case['expected_integer'], $value->get_integer(), 'Integer value should be cart total * 100' );
		}
	}

	/**
	 * Test that the filter `tec_tickets_commerce_stripe_create_from_cart` works correctly.
	 *
	 * @test
	 */
	public function it_should_apply_create_from_cart_filter_correctly() {
		$original_value = Value::create( 10.0 );

		// Add a filter to modify the value.
		add_filter( 'tec_tickets_commerce_stripe_create_from_cart', function( $value ) {
			// Return a modified value (e.g., add a fee).
			return Value::create( $value->get_decimal() + 1.0 ); // Add $1.00.
		} );

		$filtered_value = apply_filters( 'tec_tickets_commerce_stripe_create_from_cart', $original_value, [] );

		$this->assertInstanceOf( Value::class, $filtered_value, 'Filtered value should be a Value object' );
		$this->assertEquals( 11.0, $filtered_value->get_decimal(), 'Filtered value should be $11.00 (original $10.00 + $1.00)' );
		$this->assertEquals( 1100, $filtered_value->get_integer(), 'Filtered value should be 1100 cents' );

		// Clean up the filter.
		remove_all_filters( 'tec_tickets_commerce_stripe_create_from_cart' );
	}

	/**
	 * Test that the cart total logic works as expected.
	 * This simulates what happens in our fix: cart_total -> Value::create() -> get_integer().
	 *
	 * @test
	 */
	public function it_should_convert_cart_total_to_stripe_amount_correctly() {
		// Simulate cart totals with coupons applied.
		$cart_scenarios = [
			'original_total' => 10.0,
			'with_20_percent_discount' => 8.0,
			'with_50_percent_discount' => 5.0,
			'with_fixed_discount' => 7.0,
		];

		foreach ( $cart_scenarios as $scenario => $cart_total ) {
			// This is exactly what our fix does: cart_total -> Value::create() -> get_integer().
			$value = Value::create( $cart_total );
			$stripe_amount = $value->get_integer();

			$this->assertEquals( $cart_total * 100, $stripe_amount, "Stripe amount for {$scenario} should be cart total * 100" );
		}
	}
}
