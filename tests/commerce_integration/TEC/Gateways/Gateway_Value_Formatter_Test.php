<?php

namespace TEC\Tickets\Commerce\Gateways;

use TEC\Tickets\Commerce\Utils\Value;
use TEC\Tickets\Commerce\Utils\Currency;
use TEC\Tickets\Commerce\Gateways\Stripe\Gateway as Stripe_Gateway;
use TEC\Tickets\Commerce\Values\Precision_Value;
use TEC\Tickets\Commerce\Values\Gateway_Value;
use TEC\Tickets\Commerce\Values\Legacy_Value_Factory;
use Codeception\TestCase\WPTestCase;

class Gateway_Value_Formatter_Test extends WPTestCase {

	/**
	 * Set up test environment before each test.
	 *
	 * @since TBD
	 *
	 * @before
	 */
	public function setUpTestEnvironment() {
		// Store the original currency setting
		$this->original_currency = tribe_get_option( Currency::$currency_code_option, 'USD' );

		// Reset currency to a known state (USD) to prevent pollution from data providers.
		tribe_update_option( Currency::$currency_code_option, 'USD' );
	}

	/**
	 * Clean up test environment after each test.
	 *
	 * @since TBD
	 *
	 * @after
	 */
	public function tearDownTestEnvironment() {
		// Always restore the original currency setting to prevent test pollution.
		if ( isset( $this->original_currency ) ) {
			tribe_update_option( Currency::$currency_code_option, $this->original_currency );
		} else {
			// Fallback to USD if original currency wasn't stored.
			tribe_update_option( Currency::$currency_code_option, 'USD' );
		}
	}

	/**
	 * Test that the formatter does NOT change currency codes.
	 *
	 * Since the comprehensive currency test verifies currency codes are maintained for all currencies,
	 * this test simply ensures the formatter preserves currency codes and returns a new object instance.
	 *
	 * @since TBD
	 *
	 * @test
	 */
	public function formatter_never_changes_currency_code() {
		// Set currency to USD for this test.
		tribe_update_option( Currency::$currency_code_option, 'USD' );

		// Create a Value object with USD currency.
		$original_value = new Value( 10.0 );

		// Instantiate the formatter for Stripe.
		$formatter = new Gateway_Value_Formatter( tribe( Stripe_Gateway::class ) );

		// Format the value for Stripe gateway.
		$formatted_value = $formatter->format( $original_value );

		// Assert the formatted value is a different object instance (doesn't mutate).
		$this->assertNotSame( $original_value, $formatted_value, 'Formatted value should be a different object instance' );

		// Assert the formatted value maintains the SAME currency code.
		$this->assertEquals( 'USD', $formatted_value->get_currency_code(), 'Formatted value should maintain USD currency' );
		$this->assertEquals( 'USD', $original_value->get_currency_code(), 'Original value currency should remain unchanged' );
	}

	/**
	 * Data provider for testing Stripe currency formatting against the currency map.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public function stripe_currency_map_provider() {
		$currency_map = Currency::get_default_currency_map();
		$test_cases = [];

		foreach ( $currency_map as $currency_code => $currency_data ) {
			$test_cases[] = [
				'currency_code' => $currency_code,
				'input_value' => 5.0,
				'currency_precision' => $currency_data['decimal_precision'] ?? 2,
				'expected_stripe_precision' => $this->get_expected_stripe_precision( $currency_code ),
				'expected_stripe_string' => $this->get_expected_stripe_string( $currency_code, 5.0 ),
				'expected_stripe_integer' => $this->get_expected_stripe_integer( $currency_code, 5.0 ),
			];
		}

		return $test_cases;
	}

	/**
	 * Get the expected Stripe precision for a currency using the actual gateway normalization.
	 *
	 * @since TBD
	 *
	 * @param string $currency_code The currency code.
	 *
	 * @return int The expected Stripe precision.
	 */
	private function get_expected_stripe_precision( $currency_code ) {
		// Use the actual Stripe gateway to normalize a test value and get the precision.
		$stripe_gateway = tribe( Stripe_Gateway::class );
		$test_precision_value = new Precision_Value( 5.0, Currency::get_currency_precision( $currency_code ) );
		$gateway_value = new Gateway_Value( $stripe_gateway, $test_precision_value, $currency_code );

		return $gateway_value->get_precision_value()->get_precision();
	}

	/**
	 * Get the expected Stripe string for a currency and value using actual gateway normalization.
	 *
	 * @since TBD
	 *
	 * @param string $currency_code The currency code.
	 * @param float  $value The value.
	 *
	 * @return string The expected Stripe string.
	 */
	private function get_expected_stripe_string( $currency_code, $value ) {
		// Store the original currency to restore later.
		$original_currency = tribe_get_option( Currency::$currency_code_option, 'USD' );

		try {
			// Set the currency context so Value formats correctly.
			tribe_update_option( Currency::$currency_code_option, $currency_code );

			// Use the actual Stripe gateway to normalize the value and convert back to Value.
			// Start with the currency's default precision (same as input Value would have).
			$stripe_gateway = tribe( Stripe_Gateway::class );
			$starting_precision = Currency::get_currency_precision( $currency_code );
			$test_precision_value = new Precision_Value( $value, $starting_precision );
			$gateway_value = new Gateway_Value( $stripe_gateway, $test_precision_value, $currency_code );
			$normalized_precision_value = $gateway_value->get_precision_value();
			$legacy_value = Legacy_Value_Factory::to_legacy_value( $normalized_precision_value );

			// Update the Value object to recalculate internal values after precision change.
			$legacy_value->update();

			return $legacy_value->get_string();
		} finally {
			// Always restore the original currency, even if an exception occurs.
			tribe_update_option( Currency::$currency_code_option, $original_currency );
		}
	}

	/**
	 * Get the expected Stripe integer for a currency and value using actual gateway normalization.
	 *
	 * @since TBD
	 *
	 * @param string $currency_code The currency code.
	 * @param float  $value The value.
	 *
	 * @return int The expected Stripe integer.
	 */
	private function get_expected_stripe_integer( $currency_code, $value ) {
		// Store the original currency to restore later.
		$original_currency = tribe_get_option( Currency::$currency_code_option, 'USD' );

		try {
			// Set the currency context so Value formats correctly.
			tribe_update_option( Currency::$currency_code_option, $currency_code );

			// Use the actual Stripe gateway to normalize the value and convert back to Value.
			// Start with the currency's default precision (same as input Value would have).
			$stripe_gateway = tribe( Stripe_Gateway::class );
			$starting_precision = Currency::get_currency_precision( $currency_code );
			$test_precision_value = new Precision_Value( $value, $starting_precision );
			$gateway_value = new Gateway_Value( $stripe_gateway, $test_precision_value, $currency_code );
			$normalized_precision_value = $gateway_value->get_precision_value();
			$legacy_value = Legacy_Value_Factory::to_legacy_value( $normalized_precision_value );

			// Update the Value object to recalculate internal values after precision change.
			$legacy_value->update();

			return $legacy_value->get_integer();
		} finally {
			// Always restore the original currency, even if an exception occurs.
			tribe_update_option( Currency::$currency_code_option, $original_currency );
		}
	}


	/**
	 * Test that Stripe formatter handles all currencies correctly based on the currency map.
	 *
	 * @since TBD
	 *
	 * @dataProvider stripe_currency_map_provider
	 * @test
	 */
	public function stripe_formatter_handles_all_currencies_from_map( $currency_code, $input_value, $currency_precision, $expected_stripe_precision, $expected_stripe_string, $expected_stripe_integer ) {
		// Set currency for this test.
		tribe_update_option( Currency::$currency_code_option, $currency_code );

		// Create a Value object - it will get the currency's natural precision.
		$original_value = new Value( $input_value );

		// Verify the original value has the correct currency and precision.
		$this->assertEquals( $currency_code, $original_value->get_currency_code(), "Original value should have {$currency_code} currency" );
		$this->assertEquals( $currency_precision, $original_value->get_precision(), "Original value should have precision {$currency_precision} for {$currency_code}" );

		// Instantiate the formatter for Stripe.
		$formatter = new Gateway_Value_Formatter( tribe( Stripe_Gateway::class ) );

		// Format the value for Stripe gateway.
		$formatted_value = $formatter->format( $original_value );

		// Assert the formatted value is correct for Stripe.
		$this->assertNotSame( $original_value, $formatted_value, "Formatted value should be a different object instance for {$currency_code}" );
		$this->assertEquals( $expected_stripe_string, $formatted_value->get_string(), "Formatted value should be '{$expected_stripe_string}' for {$currency_code}" );
		$this->assertEquals( $expected_stripe_integer, $formatted_value->get_integer(), "Formatted value integer should be {$expected_stripe_integer} for {$currency_code}" );
		$this->assertEquals( $expected_stripe_precision, $formatted_value->get_precision(), "Formatted value should have precision {$expected_stripe_precision} for {$currency_code}" );
		$this->assertEquals( $currency_code, $formatted_value->get_currency_code(), "Formatted value should maintain {$currency_code} currency" );
	}


}
