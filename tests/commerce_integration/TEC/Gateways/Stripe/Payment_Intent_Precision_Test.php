<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use Generator;
use TEC\Tickets\Commerce\Utils\Value;
use TEC\Tickets\Commerce\Utils\Currency;
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
	 * Data provider for testing all currencies with all WordPress option settings.
	 *
	 * This comprehensive test ensures our fix works correctly regardless of
	 * WordPress display settings, catching any "oops" scenarios.
	 *
	 * @since TBD
	 *
	 * @return Generator
	 */
	public function all_currencies_with_wordpress_options_provider() {
		$currency_map = Currency::get_default_currency_map();
		$wordpress_precisions = [ 0, 1, 2 ]; // Test all possible WordPress option values
		
		foreach ( $currency_map as $currency_code => $currency_data ) {
			$currency_decimal_precision = $currency_data['decimal_precision'];
			$input_value = 1.0; // Test with 1 unit of currency
			
			foreach ( $wordpress_precisions as $wp_precision ) {
				// Calculate expected amount based on CURRENCY precision (not WordPress option).
				if ( $currency_decimal_precision === 0 ) {
					// Zero-decimal currencies: 1 unit = 1 cent (regardless of WordPress option).
					$expected_amount = '1';
				} else {
					// Two-decimal currencies: 1 unit = 100 cents (regardless of WordPress option).
					$expected_amount = '100';
				}
				
				yield "currency_{$currency_code}_wp_precision_{$wp_precision}" => [
					'currency_code' => $currency_code,
					'input_value' => $input_value,
					'wp_precision' => $wp_precision, 
					'currency_precision' => $currency_decimal_precision, 
					'expected_amount' => $expected_amount,
					'description' => "Currency {$currency_code} with WordPress option {$wp_precision} should send amount {$expected_amount} (using currency precision {$currency_decimal_precision})"
				];
			}
		}
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
	 * Test that the filter can be used to change currency precision.
	 *
	 * @since TBD
	 *
	 * @test
	 */
	public function create_method_respects_precision_filter() {
		// Add filter to change currency precision to 3.
		add_filter( 'tec_tickets_commerce_stripe_currency_precision', function( $precision, $currency_code ) {
			return 3;
		}, 10, 2 );

		// Create a value with precision 2.
		$value = new Value( 4.0 );
		$value->set_precision( 2 );

		$result = Payment_Intent::create( $value );

		// Verify the result has precision 3 (4.000 = 4000).
		$this->assertEquals( '4000', $result['amount'], 'Filter should change currency precision to 3' );

		remove_all_filters( 'tec_tickets_commerce_stripe_currency_precision' );
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

	/**
	 * Test that ALL currencies work correctly with ALL WordPress option settings.
	 *
	 * This comprehensive test ensures our fix works correctly regardless of
	 * WordPress display settings, catching any "oops" scenarios.
	 *
	 * Test Coverage:
	 * - Every currency in the Currency class map
	 * - Every possible WordPress option setting (0, 1, 2 decimals)
	 * - Validates that Stripe API receives correct amounts regardless of display settings
	 *
	 * @since TBD
	 *
	 * @test
	 * @dataProvider all_currencies_with_wordpress_options_provider
	 */
	public function create_method_handles_all_currencies_with_all_wordpress_options( $currency_code, $input_value, $wp_precision, $currency_precision, $expected_amount, $description ) {
		// Store original settings.
		$original_currency = tribe_get_option( Currency::$currency_code_option );
		$original_precision = tribe_get_option( \TEC\Tickets\Commerce\Settings::$option_currency_number_of_decimals );
		
		// Set up the test scenario.
		tribe_update_option( Currency::$currency_code_option, $currency_code );
		tribe_update_option( \TEC\Tickets\Commerce\Settings::$option_currency_number_of_decimals, $wp_precision );
		
		// Create a value that would be affected by the WordPress option.
		$value = new Value( $input_value );
		$value->set_precision( $wp_precision ); // This simulates what happens when WordPress option is applied

		// Call the create method.
		$result = Payment_Intent::create( $value );

		// Verify our fix works: should use currency precision, not WordPress option.
		$this->assertEquals( $expected_amount, $result['amount'], $description );
		
		// Verify the currency code is preserved.
		$this->assertEquals( $currency_code, $result['currency'], "Currency code should be preserved for {$currency_code}" );
		
		// Restore original settings.
		tribe_update_option( Currency::$currency_code_option, $original_currency );
		tribe_update_option( \TEC\Tickets\Commerce\Settings::$option_currency_number_of_decimals, $original_precision );
	}

}
