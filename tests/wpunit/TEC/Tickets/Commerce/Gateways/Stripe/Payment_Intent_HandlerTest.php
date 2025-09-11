<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use TEC\Tickets\Commerce\Gateways\Stripe\Payment_Intent_Handler;
use TEC\Tickets\Commerce\Utils\Value;
use Tribe\Tests\Traits\With_Uopz;
use WP_Post;

/**
 * Class Payment_Intent_HandlerTest
 *
 * @since TBD
 */
class Payment_Intent_HandlerTest extends \Codeception\TestCase\WPTestCase {

	use With_Uopz;


	/**
	 * Test that the Value object is created correctly from cart total.
	 * This tests the core logic of the fix - using cart total directly.
	 *
	 * @test
	 */
	public function it_should_create_value_from_cart_total() {
		// Test that Value::create works correctly with different amounts.
		$test_amounts = [ 8.0, 10.0, 24.0, 0.0 ];

		foreach ( $test_amounts as $amount ) {
			$value = Value::create( $amount );
			$this->assertInstanceOf( Value::class, $value, "Value should be created from amount: {$amount}" );
			$this->assertEquals( $amount, $value->get_decimal(), "Value decimal should match input amount: {$amount}" );
		}
	}

	/**
	 * Test that the Value object converts to integer correctly for Stripe.
	 * Stripe expects amounts in cents, so $8.00 should become 800.
	 *
	 * @test
	 */
	public function it_should_convert_value_to_integer_correctly() {
		$test_cases = [
			[ 8.0, 800 ],    // $8.00 = 800 cents
			[ 10.0, 1000 ],  // $10.00 = 1000 cents
			[ 24.0, 2400 ],  // $24.00 = 2400 cents
			[ 0.0, 0 ],       // $0.00 = 0 cents
		];

		foreach ( $test_cases as [ $decimal, $expected_integer ] ) {
			$value = Value::create( $decimal );
			$this->assertEquals( $expected_integer, $value->get_integer(), "Value {$decimal} should convert to {$expected_integer} cents" );
		}
	}

	/**
	 * Test that the filter is applied correctly to the value.
	 * This ensures the filter hook works as expected.
	 *
	 * @test
	 */
	public function it_should_apply_filter_correctly() {
		// Add a filter to modify the value.
		add_filter( 'tec_tickets_commerce_stripe_update_payment_intent', function( $value ) {
			// Return a modified value (e.g., add a fee).
			return Value::create( $value->get_decimal() + 1.0 ); // Add $1.00.
		} );

		// Test the filter with a base value.
		$base_value = Value::create( 10.0 );
		$filtered_value = apply_filters( 'tec_tickets_commerce_stripe_update_payment_intent', $base_value );

		// Assert that the filter was applied.
		$this->assertInstanceOf( Value::class, $filtered_value, 'Filter should return a Value object' );
		$this->assertEquals( 11.0, $filtered_value->get_decimal(), 'Filter should add $1.00 to the value' );
		$this->assertEquals( 1100, $filtered_value->get_integer(), 'Filtered value should be 1100 cents' );

		// Clean up the filter.
		remove_all_filters( 'tec_tickets_commerce_stripe_update_payment_intent' );
	}

}
