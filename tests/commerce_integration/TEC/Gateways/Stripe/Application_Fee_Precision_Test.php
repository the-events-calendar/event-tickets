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
	 * Data provider for testing application fee calculation with Stripe-specific formatting.
	 *
	 * This test ensures application fees are calculated and formatted correctly using
	 * the new Gateway_Value_Formatter with Stripe hooks.
	 *
	 * @since TBD
	 *
	 * @return \Generator
	 */
	public function stripe_application_fee_calculation_provider() {
		// Test key currencies with their expected Stripe formatting
		$test_cases = [
			// Zero-decimal currencies
			'JPY' => [
				'currency_code' => 'JPY',
				'input_value' => 100.0,
				'expected_fee_integer' => 2, // 2% of 100 = 2, Stripe format: 2
				'expected_fee_precision' => 0,
				'description' => 'JPY should use 0 decimals for Stripe'
			],
			'KRW' => [
				'currency_code' => 'KRW',
				'input_value' => 100.0,
				'expected_fee_integer' => 2, // 2% of 100 = 2, Stripe format: 2
				'expected_fee_precision' => 0,
				'description' => 'KRW should use 0 decimals for Stripe'
			],
			// Special case currencies
			'HUF' => [
				'currency_code' => 'HUF',
				'input_value' => 100.0,
				'expected_fee_integer' => 2, // 2% of 100 = 2, Stripe format: 2 (0 decimals for payouts)
				'expected_fee_precision' => 0,
				'description' => 'HUF should use 0 decimals for Stripe payouts'
			],
			'TWD' => [
				'currency_code' => 'TWD',
				'input_value' => 100.0,
				'expected_fee_integer' => 2, // 2% of 100 = 2, Stripe format: 2 (0 decimals for payouts)
				'expected_fee_precision' => 0,
				'description' => 'TWD should use 0 decimals for Stripe payouts'
			],
			'ISK' => [
				'currency_code' => 'ISK',
				'input_value' => 100.0,
				'expected_fee_integer' => 200, // 2% of 100 = 2, Stripe format: 200 (2 decimals for backwards compatibility)
				'expected_fee_precision' => 2,
				'description' => 'ISK should use 2 decimals for Stripe backwards compatibility'
			],
			'UGX' => [
				'currency_code' => 'UGX',
				'input_value' => 100.0,
				'expected_fee_integer' => 200, // 2% of 100 = 2, Stripe format: 200 (2 decimals for backwards compatibility)
				'expected_fee_precision' => 2,
				'description' => 'UGX should use 2 decimals for Stripe backwards compatibility'
			],
			// Two-decimal currencies
			'USD' => [
				'currency_code' => 'USD',
				'input_value' => 100.0,
				'expected_fee_integer' => 200, // 2% of 100 = 2, Stripe format: 200 (2 decimals)
				'expected_fee_precision' => 2,
				'description' => 'USD should use 2 decimals for Stripe'
			],
			'EUR' => [
				'currency_code' => 'EUR',
				'input_value' => 100.0,
				'expected_fee_integer' => 200, // 2% of 100 = 2, Stripe format: 200 (2 decimals)
				'expected_fee_precision' => 2,
				'description' => 'EUR should use 2 decimals for Stripe'
			],
		];

		foreach ( $test_cases as $test_case ) {
			yield $test_case['currency_code'] => $test_case;
		}
	}

	/**
	 * Test that application fees are calculated and formatted correctly using Stripe-specific rules.
	 *
	 * This test ensures application fees use the new Gateway_Value_Formatter with Stripe hooks
	 * to apply the correct precision formatting for each currency type.
	 *
	 * @since TBD
	 *
	 * @test
	 * @dataProvider stripe_application_fee_calculation_provider
	 */
	public function calculate_method_uses_stripe_formatting( 
		$currency_code, 
		$input_value, 
		$expected_fee_integer, 
		$expected_fee_precision, 
		$description 
	) {
		// Set currency for this test.
		tribe_update_option( Currency::$currency_code_option, $currency_code );

		// Create a Value object - it will get the currency's natural precision.
		$value = new Value( $input_value );

		// Call the calculate method (now uses Gateway_Value_Formatter internally).
		$fee = Application_Fee::calculate( $value );

		// Verify the fee is calculated and formatted correctly for Stripe.
		$this->assertEquals( $expected_fee_integer, $fee->get_integer(), $description );
		$this->assertEquals( $expected_fee_precision, $fee->get_precision(), "Fee precision should match Stripe requirements for {$currency_code}" );
		$this->assertEquals( $currency_code, $fee->get_currency_code(), "Fee currency should match input currency for {$currency_code}" );
	}

	/**
	 * Test specific edge cases for application fee calculation with Stripe formatting.
	 *
	 * @since TBD
	 *
	 * @test
	 */
	public function calculate_method_handles_edge_cases() {
		// Test 1: JPY with very small amount (1 yen).
		tribe_update_option( Currency::$currency_code_option, 'JPY' );
		$value_1 = new Value( 1.0 );
		$fee_1 = Application_Fee::calculate( $value_1 );
		
		// 2% of 1 = 0.02, but for JPY (Stripe format: 0 decimals), this should round to 0.
		$this->assertEquals( 0, $fee_1->get_integer(), 'JPY 1 yen should have 0 fee (2% of 1 = 0.02, rounds to 0)' );
		$this->assertEquals( 0, $fee_1->get_precision(), 'JPY fee should have precision 0 for Stripe' );
		
		// Test 2: USD with very small amount (1 cent).
		tribe_update_option( Currency::$currency_code_option, 'USD' );
		$value_2 = new Value( 0.01 );
		$fee_2 = Application_Fee::calculate( $value_2 );
		
		// 2% of 0.01 = 0.0002, but for USD (Stripe format: 2 decimals), this should round to 0.
		$this->assertEquals( 0, $fee_2->get_integer(), 'USD 1 cent should have 0 fee (2% of 0.01 = 0.0002, rounds to 0)' );
		$this->assertEquals( 2, $fee_2->get_precision(), 'USD fee should have precision 2 for Stripe' );
		
		// Test 3: Large amount to ensure calculation works.
		$value_3 = new Value( 1000.0 );
		$fee_3 = Application_Fee::calculate( $value_3 );
		
		// 2% of 1000 = 20, for USD (Stripe format: 2 decimals), this should be 2000 cents.
		$this->assertEquals( 2000, $fee_3->get_integer(), 'USD $1000 should have $20 fee (2000 cents)' );
		$this->assertEquals( 2, $fee_3->get_precision(), 'USD fee should have precision 2 for Stripe' );
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
	 * Test the specific bug that was fixed: JPY fee precision with Stripe formatting.
	 *
	 * @since TBD
	 *
	 * @test
	 */
	public function calculate_method_fixes_jpy_precision_bug() {
		// Set up the exact bug scenario that occurred in production.
		tribe_update_option( Currency::$currency_code_option, 'JPY' );

		// Create a value - it will get JPY's natural precision.
		$value = new Value( 300.0 );

		// Call the calculate method (now uses Gateway_Value_Formatter internally).
		$fee = Application_Fee::calculate( $value );

		// BEFORE our fix: This would have failed with fee integer: 600
		// AFTER our fix: This should pass with fee integer: 6 (Stripe format: 0 decimals)
		$this->assertEquals( 6, $fee->get_integer(), 'JPY 300 yen should have 6 yen fee, not 600' );
		$this->assertEquals( 0, $fee->get_precision(), 'JPY fee should have precision 0 for Stripe' );
		$this->assertEquals( 'JPY', $fee->get_currency_code(), 'Fee currency should be JPY' );
	}
}
