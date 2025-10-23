<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use TEC\Tickets\Commerce\Utils\Currency;
use TEC\Tickets\Commerce\Utils\Value;
use TEC\Tickets\Commerce\Settings;
use Codeception\TestCase\WPTestCase;
use Tribe\Tests\Traits\With_Uopz;

/**
 * Test Application_Fee precision handling for Stripe API.
 *
 * @since TBD
 */
class Application_Fee_Precision_Test extends WPTestCase {

	use With_Uopz;

	/**
	 * Set up mocks before all tests.
	 *
	 * @since TBD
	 */
	public function setUpMocks() {
		// Mock the licensed plugin check to always return false (so fees are calculated).
		$this->set_class_fn_return( Settings::class, 'is_licensed_plugin', false );
	}

	/**
	 * Data provider for testing application fee calculation with different currencies and WordPress options.
	 *
	 * This comprehensive test ensures application fees are calculated correctly regardless of
	 * WordPress display settings, catching any "oops" scenarios.
	 *
	 * @since TBD
	 *
	 * @return \Generator
	 */
	public function application_fee_calculation_provider() {
		$currency_map = Currency::get_default_currency_map();
		$wordpress_precisions = [ 0, 1, 2 ]; // Test all possible WordPress option values
		
		foreach ( $currency_map as $currency_code => $currency_data ) {
			$currency_decimal_precision = $currency_data['decimal_precision'];
			$input_value = 100.0; // Test with 100 units of currency
			
			foreach ( $wordpress_precisions as $wp_precision ) {
				// Calculate expected fee based on CURRENCY precision (not WordPress option).
				$fee_percentage = 0.02; // 2%
				$expected_fee_decimal = $input_value * $fee_percentage; // 100 * 0.02 = 2
				
				// For zero-decimal currencies, the fee should be rounded to the nearest whole number.
				if ( $currency_decimal_precision === 0 ) {
					$expected_fee_integer = (int) round( $expected_fee_decimal ); // 2
				} else {
					// For two-decimal currencies, the fee should be in cents.
					$expected_fee_integer = (int) round( $expected_fee_decimal * 100 ); // 200
				}
				
				yield "currency_{$currency_code}_wp_precision_{$wp_precision}" => [
					'currency_code' => $currency_code,
					'input_value' => $input_value,
					'wp_precision' => $wp_precision,
					'currency_precision' => $currency_decimal_precision,
					'expected_fee_integer' => $expected_fee_integer,
					'expected_fee_precision' => $currency_decimal_precision,
					'description' => "Currency {$currency_code} with WordPress option {$wp_precision} should calculate fee {$expected_fee_integer} (using currency precision {$currency_decimal_precision})"
				];
			}
		}
	}

	/**
	 * Test that application fees are calculated correctly for all currencies with all WordPress option settings.
	 *
	 * This comprehensive test ensures our fix works correctly regardless of
	 * WordPress display settings, catching any "oops" scenarios.
	 *
	 * Test Coverage:
	 * - Every currency in the Currency class map
	 * - Every possible WordPress option setting (0, 1, 2 decimals)
	 * - Validates that application fees are calculated correctly regardless of display settings
	 * - Catches the exact bug that occurred: fee precision not matching currency precision
	 *
	 * @since TBD
	 *
	 * @test
	 * @dataProvider application_fee_calculation_provider
	 */
	public function calculate_method_handles_all_currencies_with_all_wordpress_options( 
		$currency_code, 
		$input_value, 
		$wp_precision, 
		$currency_precision, 
		$expected_fee_integer, 
		$expected_fee_precision, 
		$description 
	) {
		// Store original settings.
		$original_currency = tribe_get_option( Currency::$currency_code_option );
		$original_precision = tribe_get_option( Settings::$option_currency_number_of_decimals );
		
		// Set up the test scenario.
		tribe_update_option( Currency::$currency_code_option, $currency_code );
		tribe_update_option( Settings::$option_currency_number_of_decimals, $wp_precision );
		
		// Create a value with the correct currency precision (not WordPress option).
		$value = new Value( $input_value );
		$value->set_precision( $currency_precision ); // Use currency precision, not WordPress option

		// Call the calculate method.
		$fee = Application_Fee::calculate( $value );

		// Verify our fix works: should use currency precision, not WordPress option.
		$this->assertEquals( $expected_fee_integer, $fee->get_integer(), $description );
		$this->assertEquals( $expected_fee_precision, $fee->get_precision(), "Fee precision should match currency precision for {$currency_code}" );
		$this->assertEquals( $currency_code, $fee->get_currency_code(), "Fee currency should match input currency for {$currency_code}" );
		
		// Restore original settings.
		tribe_update_option( Currency::$currency_code_option, $original_currency );
		tribe_update_option( Settings::$option_currency_number_of_decimals, $original_precision );
	}

	/**
	 * Test specific edge cases for application fee calculation.
	 *
	 * @since TBD
	 *
	 * @test
	 */
	public function calculate_method_handles_edge_cases() {
		// Store original settings.
		$original_currency = tribe_get_option( Currency::$currency_code_option );
		$original_precision = tribe_get_option( Settings::$option_currency_number_of_decimals );
		
		// Test 1: JPY with very small amount (1 yen).
		tribe_update_option( Currency::$currency_code_option, 'JPY' );
		tribe_update_option( Settings::$option_currency_number_of_decimals, 2 );
		
		$value_1 = new Value( 1.0 );
		$value_1->set_precision( 0 ); // JPY precision
		$fee_1 = Application_Fee::calculate( $value_1 );
		
		// 2% of 1 = 0.02, but for JPY (precision 0), this should round to 0.
		$this->assertEquals( 0, $fee_1->get_integer(), 'JPY 1 yen should have 0 fee (2% of 1 = 0.02, rounds to 0)' );
		$this->assertEquals( 0, $fee_1->get_precision(), 'JPY fee should have precision 0' );
		
		// Test 2: USD with very small amount (1 cent).
		tribe_update_option( Currency::$currency_code_option, 'USD' );
		tribe_update_option( Settings::$option_currency_number_of_decimals, 0 );
		
		$value_2 = new Value( 0.01 );
		$value_2->set_precision( 2 ); // USD precision
		$fee_2 = Application_Fee::calculate( $value_2 );
		
		// 2% of 0.01 = 0.0002, but for USD (precision 2), this should round to 0.
		$this->assertEquals( 0, $fee_2->get_integer(), 'USD 1 cent should have 0 fee (2% of 0.01 = 0.0002, rounds to 0)' );
		$this->assertEquals( 2, $fee_2->get_precision(), 'USD fee should have precision 2' );
		
		// Test 3: Large amount to ensure calculation works.
		$value_3 = new Value( 1000.0 );
		$value_3->set_precision( 2 ); // USD precision
		$fee_3 = Application_Fee::calculate( $value_3 );
		
		// 2% of 1000 = 20, for USD (precision 2), this should be 2000 cents.
		$this->assertEquals( 2000, $fee_3->get_integer(), 'USD $1000 should have $20 fee (2000 cents)' );
		$this->assertEquals( 2, $fee_3->get_precision(), 'USD fee should have precision 2' );
		
		// Restore original settings.
		tribe_update_option( Currency::$currency_code_option, $original_currency );
		tribe_update_option( Settings::$option_currency_number_of_decimals, $original_precision );
	}

	/**
	 * Test that licensed plugins return zero fee.
	 *
	 * @since TBD
	 *
	 * @test
	 */
	public function calculate_method_returns_zero_for_licensed_plugins() {
		// Mock the licensed plugin check to return true.
		$this->set_class_fn_return( Settings::class, 'is_licensed_plugin', true );
		
		$value = new Value( 100.0 );
		$fee = Application_Fee::calculate( $value );
		
		$this->assertEquals( 0, $fee->get_integer(), 'Licensed plugins should return zero fee' );
		$this->assertEquals( 0, $fee->get_float(), 'Licensed plugins should return zero fee' );
	}

	/**
	 * Test the specific bug that was fixed: JPY fee precision.
	 *
	 * @since TBD
	 *
	 * @test
	 */
	public function calculate_method_fixes_jpy_precision_bug() {
		// Store original settings.
		$original_currency = tribe_get_option( Currency::$currency_code_option );
		$original_precision = tribe_get_option( Settings::$option_currency_number_of_decimals );
		
		// Set up the exact bug scenario that occurred in production.
		tribe_update_option( Currency::$currency_code_option, 'JPY' );
		tribe_update_option( Settings::$option_currency_number_of_decimals, 2 );
		
		// Create a value with the correct currency precision.
		$value = new Value( 300.0 );
		$value->set_precision( 0 ); // JPY precision

		// Call the calculate method.
		$fee = Application_Fee::calculate( $value );

		// BEFORE our fix: This would have failed with fee integer: 600
		// AFTER our fix: This should pass with fee integer: 6
		$this->assertEquals( 6, $fee->get_integer(), 'JPY 300 yen should have 6 yen fee, not 600' );
		$this->assertEquals( 0, $fee->get_precision(), 'JPY fee should have precision 0' );
		$this->assertEquals( 'JPY', $fee->get_currency_code(), 'Fee currency should be JPY' );
		
		// Restore original settings.
		tribe_update_option( Currency::$currency_code_option, $original_currency );
		tribe_update_option( Settings::$option_currency_number_of_decimals, $original_precision );
	}
}
