<?php

namespace TEC\Tickets\Commerce\Gateways;

use TEC\Tickets\Commerce\Utils\Value;
use TEC\Tickets\Commerce\Utils\Currency;
use TEC\Tickets\Commerce\Gateways\Stripe\Gateway as Stripe_Gateway;
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
		
		// Ensure Stripe hooks are registered for tests
		$this->register_stripe_hooks();
	}

	/**
	 * Register Stripe hooks for testing.
	 *
	 * @since TBD
	 */
	private function register_stripe_hooks() {
		// Manually register the Stripe currency precision filter for testing
		add_filter( 'tec_tickets_commerce_gateway_value_formatter_stripe_currency_map', [ $this, 'filter_stripe_currency_precision' ], 10, 3 );
	}

	/**
	 * Filter Stripe currency precision based on Stripe's specific requirements.
	 * This mirrors the logic from Stripe/Hooks.php for testing purposes.
	 *
	 * @since TBD
	 *
	 * @param array  $currency_data The currency data from the map.
	 * @param string $currency_code The currency code.
	 * @param string $gateway       The gateway name.
	 *
	 * @return array The modified currency data.
	 */
	public function filter_stripe_currency_precision( $currency_data, $currency_code, $gateway ) {
		// Only apply Stripe-specific logic for the Stripe gateway.
		if ( 'stripe' !== $gateway ) {
			return $currency_data;
		}

		// Apply Stripe's currency precision rules.
		$stripe_precision = $this->get_stripe_precision( $currency_code, $currency_data['decimal_precision'] ?? 2 );
		
		// Update the currency data with Stripe's precision.
		$currency_data['decimal_precision'] = $stripe_precision;

		return $currency_data;
	}

	/**
	 * Get the appropriate precision for Stripe based on their currency requirements.
	 * This mirrors the logic from Stripe/Hooks.php for testing purposes.
	 *
	 * @since TBD
	 *
	 * @param string $currency_code The currency code.
	 * @param int    $default_precision The default precision from currency data.
	 *
	 * @return int The precision to use for Stripe.
	 */
	private function get_stripe_precision( $currency_code, $default_precision ) {
		// Stripe special case currencies (these override the zero-decimal list).
		$special_cases = [
			'ISK' => 2, // Backwards compatibility requires 2 decimals
			'HUF' => 0, // Zero-decimal for payouts
			'TWD' => 0, // Zero-decimal for payouts
			'UGX' => 2, // Backwards compatibility requires 2 decimals
		];

		// Check special cases first (these take priority).
		if ( isset( $special_cases[ $currency_code ] ) ) {
			return $special_cases[ $currency_code ];
		}

		// Stripe zero-decimal currencies (no multiplication needed).
		$zero_decimal_currencies = [
			'BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG',
			'RWF', 'VND', 'VUV', 'XAF', 'XOF', 'XPF'
		];

		// Check zero-decimal currencies.
		if ( in_array( $currency_code, $zero_decimal_currencies, true ) ) {
			return 0;
		}

		// Default to the currency's precision for other currencies.
		return $default_precision;
	}

	/**
	 * Clean up test environment after each test.
	 *
	 * @since TBD
	 *
	 * @after
	 */
	public function tearDownTestEnvironment() {
		// Always restore the original currency setting
		tribe_update_option( Currency::$currency_code_option, $this->original_currency );
		
		// Clean up any filters that might have been added
		remove_all_filters( 'tec_tickets_commerce_gateway_value_formatter_stripe_currency_map' );
	}

	/**
	 * Test that Stripe formatter converts JPY display value to gateway format.
	 *
	 * @since TBD
	 *
	 * @test
	 */
	public function stripe_formatter_converts_jpy_display_value_to_gateway_format() {
		// Set currency to JPY for this test.
		tribe_update_option( Currency::$currency_code_option, 'JPY' );

		// Create a Value object representing "3.00" (display format with precision 2).
		// We'll manually set the precision to 2 to simulate a display value.
		$original_value = new Value( 3.0 );
		$original_value->set_precision( 2 );
		$original_value->update(); // Recalculate internal values after precision change

		// Verify the original value is in display format.
		$this->assertEquals( '3.00', $original_value->get_string(), 'Original value should be in display format' );
		$this->assertEquals( 300, $original_value->get_integer(), 'Original value integer should be 300 (3.0 * 10^2)' );
		$this->assertEquals( 'JPY', $original_value->get_currency_code(), 'Original value should have JPY currency' );

		// Instantiate the formatter for Stripe.
		$formatter = new Gateway_Value_Formatter( tribe( Stripe_Gateway::class ) );

		// Format the value for Stripe gateway.
		$formatted_value = $formatter->format( $original_value );

		// Assert the formatted value is a different object.
		$this->assertNotSame( $original_value, $formatted_value, 'Formatted value should be a different object instance' );

		// Assert the formatted value represents "3" (gateway format) with SAME currency.
		$this->assertEquals( '3', $formatted_value->get_string(), 'Formatted value should be "3" for Stripe gateway' );
		$this->assertEquals( 3, $formatted_value->get_integer(), 'Formatted value integer should be 3 (3.0 * 10^0) for Stripe gateway' );
		$this->assertEquals( 0, $formatted_value->get_precision(), 'Formatted value should have precision 0 for Stripe gateway' );
		$this->assertEquals( 'JPY', $formatted_value->get_currency_code(), 'Formatted value should maintain JPY currency' );
	}

	/**
	 * Test that the formatter uses currency data and respects filters.
	 *
	 * @since TBD
	 *
	 * @test
	 */
	public function formatter_uses_currency_data_and_respects_filters() {
		// Set currency to JPY for this test.
		tribe_update_option( Currency::$currency_code_option, 'JPY' );

		// Create a Value object representing "5.00" (display format with precision 2).
		// We'll manually set the precision to 2 to simulate a display value.
		$original_value = new Value( 5.0 );
		$original_value->set_precision( 2 );
		$original_value->update(); // Recalculate internal values after precision change

		// Verify the original value is in display format.
		$this->assertEquals( '5.00', $original_value->get_string(), 'Original value should be in display format' );
		$this->assertEquals( 500, $original_value->get_integer(), 'Original value integer should be 500 (5.0 * 10^2)' );
		$this->assertEquals( 'JPY', $original_value->get_currency_code(), 'Original value should have JPY currency' );

		// Test the filter system by adding a custom filter.
		$filter_called = false;
		add_filter( 'tec_tickets_commerce_gateway_value_formatter_stripe_currency_map', function( $currency_data, $currency_code, $gateway ) use ( &$filter_called ) {
			$filter_called = true;
			$this->assertEquals( 'JPY', $currency_code, 'Filter should receive correct currency code' );
			$this->assertEquals( 'stripe', $gateway, 'Filter should receive correct gateway' );
			$this->assertIsArray( $currency_data, 'Filter should receive currency data array' );
			// Ensure JPY has precision 0 for Stripe (correct behavior).
			$currency_data['decimal_precision'] = 0;
			return $currency_data;
		}, 10, 3 );

		// Instantiate the formatter for Stripe.
		$formatter = new Gateway_Value_Formatter( tribe( Stripe_Gateway::class ) );

		// Format the value for Stripe gateway.
		$formatted_value = $formatter->format( $original_value );

		// Verify the filter was called.
		$this->assertTrue( $filter_called, 'Filter should have been called' );

		// Assert the formatted value uses the filtered precision with SAME currency.
		$this->assertNotSame( $original_value, $formatted_value, 'Formatted value should be a different object instance' );
		$this->assertEquals( '5', $formatted_value->get_string(), 'Formatted value should be "5" with precision 0' );
		$this->assertEquals( 5, $formatted_value->get_integer(), 'Formatted value integer should be 5 (5.0 * 10^0)' );
		$this->assertEquals( 0, $formatted_value->get_precision(), 'Formatted value should have precision 0 from filter' );
		$this->assertEquals( 'JPY', $formatted_value->get_currency_code(), 'Formatted value should maintain JPY currency' );

		// Clean up the filter.
		remove_all_filters( 'tec_tickets_commerce_gateway_value_formatter_stripe_currency_map' );
	}

	/**
	 * Test that the formatter does NOT change currency codes.
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
		$original_value->set_precision( 2 );
		$original_value->update(); // Recalculate internal values after precision change

		// Verify the original value has USD currency.
		$this->assertEquals( 'USD', $original_value->get_currency_code(), 'Original value should have USD currency' );
		$this->assertEquals( '10.00', $original_value->get_string(), 'Original value should be "10.00"' );

		// Instantiate the formatter for Stripe.
		$formatter = new Gateway_Value_Formatter( tribe( Stripe_Gateway::class ) );

		// Format the value for Stripe gateway.
		$formatted_value = $formatter->format( $original_value );

		// Assert the formatted value maintains the SAME currency.
		$this->assertEquals( 'USD', $formatted_value->get_currency_code(), 'Formatted value should maintain USD currency' );
		$this->assertEquals( '10.00', $formatted_value->get_string(), 'Formatted value should be "10.00" for USD' );
		$this->assertEquals( 2, $formatted_value->get_precision(), 'Formatted value should maintain precision 2 for USD' );
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
	 * Get the expected Stripe precision for a currency using the actual hook system.
	 *
	 * @since TBD
	 *
	 * @param string $currency_code The currency code.
	 *
	 * @return int The expected Stripe precision.
	 */
	private function get_expected_stripe_precision( $currency_code ) {
		// Get the currency data from the map.
		$currency_map = Currency::get_default_currency_map();
		$currency_data = $currency_map[ $currency_code ] ?? [];

		// Apply the Stripe filter to get the actual precision that would be used.
		$filtered_currency_data = apply_filters( 'tec_tickets_commerce_gateway_value_formatter_stripe_currency_map', $currency_data, $currency_code, 'stripe' );

		return $filtered_currency_data['decimal_precision'] ?? 2;
	}

	/**
	 * Get the expected Stripe string for a currency and value.
	 *
	 * @since TBD
	 *
	 * @param string $currency_code The currency code.
	 * @param float  $value The value.
	 *
	 * @return string The expected Stripe string.
	 */
	private function get_expected_stripe_string( $currency_code, $value ) {
		$precision = $this->get_expected_stripe_precision( $currency_code );
		
		// Set the currency context and create a temporary Value object
		tribe_update_option( Currency::$currency_code_option, $currency_code );
		
		$temp_value = new Value( $value );
		$temp_value->set_precision( $precision );
		$temp_value->update();
		
		return $temp_value->get_string();
	}

	/**
	 * Get the expected Stripe integer for a currency and value.
	 *
	 * @since TBD
	 *
	 * @param string $currency_code The currency code.
	 * @param float  $value The value.
	 *
	 * @return int The expected Stripe integer.
	 */
	private function get_expected_stripe_integer( $currency_code, $value ) {
		$precision = $this->get_expected_stripe_precision( $currency_code );
		
		// Set the currency context and create a temporary Value object
		tribe_update_option( Currency::$currency_code_option, $currency_code );
		
		$temp_value = new Value( $value );
		$temp_value->set_precision( $precision );
		$temp_value->update();
		
		return $temp_value->get_integer();
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


	/**
	 * Test that the filter system can override Stripe's built-in currency rules.
	 *
	 * @since TBD
	 *
	 * @test
	 */
	public function stripe_formatter_respects_filter_overrides() {
		// Set currency to JPY for this test.
		tribe_update_option( Currency::$currency_code_option, 'JPY' );

		// Create a Value object representing "5.00" (display format with precision 2).
		$original_value = new Value( 5.0 );
		$original_value->set_precision( 2 );
		$original_value->update();

		// Verify the original value is in display format.
		$this->assertEquals( '5.00', $original_value->get_string(), 'Original value should be in display format' );
		$this->assertEquals( 500, $original_value->get_integer(), 'Original value integer should be 500 (5.0 * 10^2)' );
		$this->assertEquals( 'JPY', $original_value->get_currency_code(), 'Original value should have JPY currency' );

		// Add a filter to override Stripe's built-in JPY logic (normally 0 decimals).
		add_filter( 'tec_tickets_commerce_gateway_value_formatter_stripe_currency_map', function( $currency_data, $currency_code, $gateway ) {
			if ( $currency_code === 'JPY' ) {
				$currency_data['decimal_precision'] = 1; // Override to 1 decimal
			}
			return $currency_data;
		}, 10, 3 );

		// Instantiate the formatter for Stripe.
		$formatter = new Gateway_Value_Formatter( tribe( Stripe_Gateway::class ) );

		// Format the value for Stripe gateway.
		$formatted_value = $formatter->format( $original_value );

		// Assert the formatted value uses the filter override (not Stripe's built-in logic).
		$this->assertNotSame( $original_value, $formatted_value, 'Formatted value should be a different object instance' );
		$this->assertEquals( '5.0', $formatted_value->get_string(), 'Formatted value should be "5.0" with precision 1 from filter override' );
		$this->assertEquals( 50, $formatted_value->get_integer(), 'Formatted value integer should be 50 (5.0 * 10^1) from filter override' );
		$this->assertEquals( 1, $formatted_value->get_precision(), 'Formatted value should have precision 1 from filter override' );
		$this->assertEquals( 'JPY', $formatted_value->get_currency_code(), 'Formatted value should maintain JPY currency' );
	}
}
