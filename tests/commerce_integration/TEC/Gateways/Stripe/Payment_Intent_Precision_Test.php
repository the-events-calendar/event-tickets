<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use Generator;
use TEC\Tickets\Commerce\Utils\Value;
use Tribe\Tests\Traits\With_Uopz;
use Codeception\TestCase\WPTestCase;

class Payment_Intent_Precision_Test extends WPTestCase {

	use With_Uopz;

	/**
	 * Set up mocks before each test.
	 *
	 * @since TBD
	 *
	 * @before
	 */
	public function setUpMocks() {
		$this->set_class_fn_return( 'TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Requests', 'post', function( $url, $query_args, $args ) {
			return $args['body'];
		}, true );
	}

	/**
	 * Data provider for testing precision normalization scenarios.
	 *
	 * @since TBD
	 *
	 * @return Generator
	 */
	public function precision_normalization_provider() {
		yield 'precision_0_should_normalize_to_2' => [
			'input_value' => 4.0,
			'input_precision' => 0,
			'expected_amount' => '400',
			'description' => 'Value with precision 0 should be normalized to precision 2 for Stripe'
		];

		yield 'precision_1_should_normalize_to_2' => [
			'input_value' => 4.5,
			'input_precision' => 1,
			'expected_amount' => '450',
			'description' => 'Value with precision 1 should be normalized to precision 2 for Stripe'
		];

		yield 'precision_2_should_remain_unchanged' => [
			'input_value' => 4.0,
			'input_precision' => 2,
			'expected_amount' => '400',
			'description' => 'Value with precision 2 should remain unchanged'
		];

		yield 'precision_3_should_normalize_to_2' => [
			'input_value' => 4.0,
			'input_precision' => 3,
			'expected_amount' => '400',
			'description' => 'Value with precision 3 should normalize to precision 2 for Stripe (4000 cents = $40.00, not $4.000)'
		];

		yield 'precision_4_should_normalize_to_2' => [
			'input_value' => 4.0,
			'input_precision' => 4,
			'expected_amount' => '400',
			'description' => 'Value with precision 4+ should normalize to precision 2 for Stripe'
		];

		yield 'decimal_value_precision_0_50_cents' => [
			'input_value' => 0.50,
			'input_precision' => 0,
			'expected_amount' => '50',
			'description' => 'Decimal value (50 cents) with precision 0 should normalize to precision 2 for Stripe'
		];

		yield 'decimal_value_precision_0' => [
			'input_value' => 4.25,
			'input_precision' => 0,
			'expected_amount' => '425',
			'description' => 'Decimal value with precision 0 should be normalized correctly'
		];

		yield 'large_value_precision_0' => [
			'input_value' => 100.0,
			'input_precision' => 0,
			'expected_amount' => '10000',
			'description' => 'Large value with precision 0 should be normalized correctly'
		];
	}

	/**
	 * Test that the create method properly normalizes precision for Stripe API.
	 *
	 * @since TBD
	 *
	 * @test
	 * @dataProvider precision_normalization_provider
	 */
	public function create_method_normalizes_precision( $input_value, $input_precision, $expected_amount, $description ) {
		$value = new Value( $input_value );
		$value->set_precision( $input_precision );

		$result = Payment_Intent::create( $value );

		$this->assertEquals( $expected_amount, $result['amount'], $description );
	}

	/**
	 * Test that the filter can be used to change minimum precision.
	 *
	 * @since TBD
	 *
	 * @test
	 */
	public function create_method_respects_precision_filter() {
		// Add filter to change minimum precision to 3.
		add_filter( 'tec_tickets_commerce_stripe_minimum_precision', function( $precision, $value ) {
			return 3;
		}, 10, 2 );

		// Create a value with precision 2.
		$value = new Value( 4.0 );
		$value->set_precision( 2 );

		$result = Payment_Intent::create( $value );

		// Verify the result has precision 3 (4.000 = 4000).
		$this->assertEquals( '4000', $result['amount'], 'Filter should change minimum precision to 3' );

		remove_all_filters( 'tec_tickets_commerce_stripe_minimum_precision' );
	}

	/**
	 * Test that the create method handles edge cases properly.
	 *
	 * @since TBD
	 *
	 * @test
	 */
	public function create_method_handles_edge_cases() {
		// Test with zero value.
		$zero_value = new Value( 0.0 );
		$zero_value->set_precision( 0 );

		$result = Payment_Intent::create( $zero_value );
		$this->assertEquals( '0', $result['amount'], 'Zero value should remain zero' );

		// Test with very small value.
		$small_value = new Value( 0.01 );
		$small_value->set_precision( 0 );

		$result = Payment_Intent::create( $small_value );
		$this->assertEquals( '1', $result['amount'], 'Small value should be normalized correctly' );
	}

	/**
	 * Test that values with adequate precision are not modified.
	 *
	 * @since TBD
	 *
	 * @test
	 */
	public function create_method_does_not_modify_adequate_precision() {
		// Create a value with precision 2.
		$value = new Value( 4.0 );
		$value->set_precision( 2 );

		$result = Payment_Intent::create( $value );

		// Verify the amount is correct (400 cents for $4.00).
		$this->assertEquals( '400', $result['amount'], 'Value with adequate precision should work correctly' );
	}
}
