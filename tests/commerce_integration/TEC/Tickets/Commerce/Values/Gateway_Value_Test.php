<?php

namespace TEC\Tickets\Commerce\Tests\Values;

use Codeception\TestCase\WPTestCase;
use Generator;
use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Gateway;
use TEC\Tickets\Commerce\Gateways\Manager as Gateway_Manager;
use TEC\Tickets\Commerce\Utils\Currency;
use TEC\Tickets\Commerce\Values\Gateway_Value;
use TEC\Tickets\Commerce\Values\Precision_Value;

/**
 * Test suite for Gateway_Value.
 *
 * Tests the Gateway_Value class across all gateways that extend Abstract_Gateway
 * to ensure the normalization logic works correctly for each gateway.
 *
 * @since TBD
 */
class Gateway_Value_Test extends WPTestCase {

	/**
	 * Set up test environment before each test.
	 *
	 * @since TBD
	 *
	 * @before
	 */
	public function setUpTestEnvironment() {
		parent::setUp();
	}

	/**
	 * Check if a gateway overrides the normalize_value_for_gateway method.
	 *
	 * @since TBD
	 *
	 * @param Abstract_Gateway $gateway The gateway instance to check.
	 *
	 * @return bool True if the method is overridden, false if using the default.
	 */
	protected function gateway_overrides_normalization( Abstract_Gateway $gateway ): bool {
		$reflection = new \ReflectionClass( $gateway );
		$method     = $reflection->getMethod( 'normalize_value_for_gateway' );

		// If the method is declared in a class other than Abstract_Gateway, it's been overridden.
		return $method->getDeclaringClass()->getName() !== Abstract_Gateway::class;
	}

	/**
	 * Data provider for gateways that use the default normalization (don't override normalize_value_for_gateway).
	 *
	 * @since TBD
	 *
	 * @return Generator
	 */
	public function gateway_provider() {
		$gateway_manager = tribe( Gateway_Manager::class );
		$gateways = $gateway_manager->get_gateways();

		if ( empty( $gateways ) ) {
			$this->markTestSkipped( 'No gateways found to test.' );
		}

		foreach ( $gateways as $gateway_key => $gateway ) {
			if ( ! $gateway instanceof Abstract_Gateway ) {
				continue;
			}

			// Only include gateways that don't override the normalization method.
			if ( $this->gateway_overrides_normalization( $gateway ) ) {
				continue;
			}

			yield $gateway_key => [
				'gateway'      => $gateway,
				'gateway_key'  => $gateway_key,
				'gateway_name' => ucfirst( $gateway_key ),
			];
		}
	}

	/**
	 * Data provider for currency and value combinations, dynamically generated from currency map.
	 *
	 * Loops through all currencies in the currency map and generates test cases
	 * with expected values based on each currency's decimal precision.
	 *
	 * @since TBD
	 *
	 * @return Generator
	 */
	public function currency_value_provider(): Generator {
		$currency_map = Currency::get_default_currency_map();

		foreach ( $currency_map as $currency_code => $currency_data ) {
			$precision = (int) ( $currency_data['decimal_precision'] ?? 2 );
			$base = 10.0;

			yield sprintf( '%s_%d', $currency_code, $precision ) => [
				'currency_code'      => $currency_code,
				'input_value'        => $base,
				'expected_precision' => $precision,
				'expected_float'     => $base,
				'expected_integer'   => (int) ( $base * ( 10 ** $precision ) ),
			];
		}
	}

	/**
	 * Data provider that combines gateways and currencies for normalization tests.
	 *
	 * Yields test cases with both gateway and currency data so the gateway name
	 * appears in the test output.
	 *
	 * @since TBD
	 *
	 * @return Generator
	 */
	public function gateway_currency_provider(): Generator {
		$gateway_manager = tribe( Gateway_Manager::class );
		$gateways = $gateway_manager->get_gateways();

		if ( empty( $gateways ) ) {
			return;
		}

		$currency_map = Currency::get_default_currency_map();

		foreach ( $gateways as $gateway_key => $gateway ) {
			if ( ! $gateway instanceof Abstract_Gateway ) {
				continue;
			}

			// Only include gateways that don't override the normalization method.
			if ( $this->gateway_overrides_normalization( $gateway ) ) {
				continue;
			}

			foreach ( $currency_map as $currency_code => $currency_data ) {
				$precision = (int) ( $currency_data['decimal_precision'] ?? 2 );
				$base = 10.0;

				yield sprintf( '%s_%s_%d', $gateway_key, $currency_code, $precision ) => [
					'gateway'          => $gateway,
					'gateway_key'      => $gateway_key,
					'gateway_name'     => ucfirst( $gateway_key ),
					'currency_code'   => $currency_code,
					'input_value'      => $base,
					'expected_precision' => $precision,
					'expected_float'   => $base,
					'expected_integer' => (int) ( $base * ( 10 ** $precision ) ),
				];
			}
		}
	}

	/**
	 * Test that Gateway_Value can be instantiated with any gateway.
	 *
	 * @since TBD
	 *
	 * @test
	 * @dataProvider gateway_provider
	 */
	public function it_should_create_gateway_value_instance( $gateway, $gateway_key, $gateway_name ) {
		$precision_value = new Precision_Value( 10.50, 2 );
		$gateway_value = new Gateway_Value( $gateway, $precision_value, 'USD' );

		$this->assertInstanceOf( Gateway_Value::class, $gateway_value, "Gateway_Value should be instantiable for {$gateway_name} gateway." );
	}

	/**
	 * Test that get() returns the correct float value for each gateway.
	 *
	 * @since TBD
	 *
	 * @test
	 * @dataProvider gateway_provider
	 */
	public function it_should_return_correct_float_value( $gateway, $gateway_key, $gateway_name ) {
		$precision_value = new Precision_Value( 10.50, 2 );
		$gateway_value = new Gateway_Value( $gateway, $precision_value, 'USD' );

		$result = $gateway_value->get();

		$this->assertIsFloat( $result, "get() should return a float for {$gateway_name} gateway." );
		$this->assertEqualsWithDelta( 10.50, $result, 0.01, "get() should return 10.50 for {$gateway_name} gateway." );
	}

	/**
	 * Test that get_integer() returns the correct integer value for each gateway.
	 *
	 * @since TBD
	 *
	 * @test
	 * @dataProvider gateway_provider
	 */
	public function it_should_return_correct_integer_value( $gateway, $gateway_key, $gateway_name ) {
		$precision_value = new Precision_Value( 10.50, 2 );
		$gateway_value = new Gateway_Value( $gateway, $precision_value, 'USD' );

		$result = $gateway_value->get_integer();

		$this->assertIsInt( $result, "get_integer() should return an integer for {$gateway_name} gateway." );
		$this->assertEquals( 1050, $result, "get_integer() should return 1050 (10.50 * 100) for {$gateway_name} gateway." );
	}

	/**
	 * Test that get_precision_value() returns the correct Precision_Value for each gateway.
	 *
	 * @since TBD
	 *
	 * @test
	 * @dataProvider gateway_provider
	 */
	public function it_should_return_precision_value( $gateway, $gateway_key, $gateway_name ) {
		$precision_value = new Precision_Value( 10.50, 2 );
		$gateway_value = new Gateway_Value( $gateway, $precision_value, 'USD' );

		$result = $gateway_value->get_precision_value();

		$this->assertInstanceOf( Precision_Value::class, $result, "get_precision_value() should return a Precision_Value for {$gateway_name} gateway." );
		$this->assertEqualsWithDelta( 10.50, $result->get(), 0.01, "Precision_Value should have correct value for {$gateway_name} gateway." );
	}

	/**
	 * Test normalization with different currencies across all gateways.
	 *
	 * @since TBD
	 *
	 * @test
	 * @dataProvider gateway_provider
	 */
	public function it_should_normalize_values_with_different_currencies( $gateway, $gateway_key, $gateway_name ) {
		// Test with USD (2 decimal places).
		$usd_value = new Precision_Value( 10.50, 2 );
		$usd_gateway_value = new Gateway_Value( $gateway, $usd_value, 'USD' );
		$this->assertEqualsWithDelta( 10.50, $usd_gateway_value->get(), 0.01, "USD normalization should work for {$gateway_name}." );
		$this->assertEquals( 1050, $usd_gateway_value->get_integer(), "USD integer value should be 1050 for {$gateway_name}." );

		// Test with JPY (0 decimal places).
		$jpy_value = new Precision_Value( 500.0, 0 );
		$jpy_gateway_value = new Gateway_Value( $gateway, $jpy_value, 'JPY' );
		$this->assertIsFloat( $jpy_gateway_value->get(), "JPY normalization should return float for {$gateway_name}." );
		$this->assertIsInt( $jpy_gateway_value->get_integer(), "JPY integer value should be an integer for {$gateway_name}." );
		// Since we're only testing default normalization, JPY should maintain 0 precision.
		$this->assertEquals( 500, $jpy_gateway_value->get_integer(), "JPY integer value should be 500 for {$gateway_name}." );
	}

	/**
	 * Test that currency code is normalized to uppercase.
	 *
	 * @since TBD
	 *
	 * @test
	 * @dataProvider gateway_provider
	 */
	public function it_should_normalize_currency_code_to_uppercase( $gateway, $gateway_key, $gateway_name ) {
		$precision_value = new Precision_Value( 10.50, 2 );

		// Test with lowercase currency code.
		$gateway_value_lower = new Gateway_Value( $gateway, $precision_value, 'usd' );
		// Test with uppercase currency code.
		$gateway_value_upper = new Gateway_Value( $gateway, $precision_value, 'USD' );

		$this->assertEqualsWithDelta(
			$gateway_value_lower->get(),
			$gateway_value_upper->get(),
			0.01,
			"Lowercase and uppercase currency codes should produce the same result for {$gateway_name}."
		);
	}

	/**
	 * Test with zero value across all gateways.
	 *
	 * @since TBD
	 *
	 * @test
	 * @dataProvider gateway_provider
	 */
	public function it_should_handle_zero_value( $gateway, $gateway_key, $gateway_name ) {
		$zero_value = new Precision_Value( 0.0, 2 );
		$gateway_value = new Gateway_Value( $gateway, $zero_value, 'USD' );

		$this->assertEquals( 0.0, $gateway_value->get(), "Zero value should be handled correctly for {$gateway_name}." );
		$this->assertEquals( 0, $gateway_value->get_integer(), "Zero integer value should be 0 for {$gateway_name}." );
	}

	/**
	 * Test with large values across all gateways.
	 *
	 * @since TBD
	 *
	 * @test
	 * @dataProvider gateway_provider
	 */
	public function it_should_handle_large_values( $gateway, $gateway_key, $gateway_name ) {
		$large_value = new Precision_Value( 999999.99, 2 );
		$gateway_value = new Gateway_Value( $gateway, $large_value, 'USD' );

		$this->assertEqualsWithDelta( 999999.99, $gateway_value->get(), 0.01, "Large values should be handled correctly for {$gateway_name}." );
		$this->assertEquals( 99999999, $gateway_value->get_integer(), "Large integer value should be correct for {$gateway_name}." );
	}

	/**
	 * Test that Precision_Value can be retrieved and used for calculations.
	 *
	 * @since TBD
	 *
	 * @test
	 * @dataProvider gateway_provider
	 */
	public function it_should_allow_precision_value_math_operations( $gateway, $gateway_key, $gateway_name ) {
		$value1 = new Precision_Value( 10.50, 2 );
		$gateway_value1 = new Gateway_Value( $gateway, $value1, 'USD' );

		$value2 = new Precision_Value( 5.25, 2 );
		$gateway_value2 = new Gateway_Value( $gateway, $value2, 'USD' );

		// Get precision values and perform math operations.
		$precision_value1 = $gateway_value1->get_precision_value();
		$precision_value2 = $gateway_value2->get_precision_value();

		$sum = $precision_value1->add( $precision_value2 );

		$this->assertEqualsWithDelta( 15.75, $sum->get(), 0.01, "Precision_Value math operations should work for {$gateway_name}." );
	}

	/**
	 * Test that different currencies normalize correctly with their expected precision.
	 *
	 * @since TBD
	 *
	 * @test
	 * @dataProvider gateway_currency_provider
	 */
	public function it_should_normalize_currencies_with_correct_precision( $gateway, $gateway_key, $gateway_name, $currency_code, $input_value, $expected_precision, $expected_float, $expected_integer ) {
		$precision_value = new Precision_Value( $input_value, $expected_precision );
		$gateway_value = new Gateway_Value( $gateway, $precision_value, $currency_code );

		$actual_float = $gateway_value->get();
		$actual_integer = $gateway_value->get_integer();

		$this->assertIsFloat( $actual_float, "Float value should be returned for {$currency_code} with {$gateway_name} gateway." );
		$this->assertIsInt( $actual_integer, "Integer value should be returned for {$currency_code} with {$gateway_name} gateway." );

		// Since we're only testing gateways with default normalization, we can assert exact values.
		$this->assertEqualsWithDelta(
			$expected_float,
			$actual_float,
			0.01,
			"Float value should match expected for {$currency_code} with {$gateway_name} gateway."
		);

		if ( $expected_precision === 0 ) {
			// For zero-decimal currencies, integer should equal float.
			$this->assertEquals(
				(int) $expected_float,
				$actual_integer,
				"Integer value should match expected for zero-decimal {$currency_code} with {$gateway_name} gateway."
			);
		} else {
			$this->assertEquals(
				$expected_integer,
				$actual_integer,
				"Integer value should match expected for {$currency_code} with {$gateway_name} gateway."
			);
		}
	}

	/**
	 * Test that the gateway's normalize_value_for_gateway method is called correctly.
	 *
	 * @since TBD
	 *
	 * @test
	 * @dataProvider gateway_provider
	 */
	public function it_should_call_gateway_normalization_method( $gateway, $gateway_key, $gateway_name ) {
		$precision_value = new Precision_Value( 10.50, 2 );

		// Create Gateway_Value which should call normalize_value_for_gateway.
		$gateway_value = new Gateway_Value( $gateway, $precision_value, 'USD' );

		// Verify the value was normalized (it should be wrapped in a Precision_Value).
		$normalized_precision_value = $gateway_value->get_precision_value();

		$this->assertInstanceOf(
			Precision_Value::class,
			$normalized_precision_value,
			"Gateway normalization should return a Precision_Value for {$gateway_name}."
		);

		// Verify the normalized value makes sense.
		$this->assertEqualsWithDelta(
			10.50,
			$normalized_precision_value->get(),
			0.01,
			"Normalized value should be correct for {$gateway_name}."
		);
	}

	/**
	 * Test that the default normalization works correctly for gateways that don't override.
	 *
	 * @since TBD
	 *
	 * @test
	 * @dataProvider gateway_provider
	 */
	public function it_should_use_default_normalization_when_gateway_has_no_override( $gateway, $gateway_key, $gateway_name ) {
		$precision_value = new Precision_Value( 10.50, 2 );
		$gateway_value   = new Gateway_Value( $gateway, $precision_value, 'USD' );

		$normalized = $gateway_value->get_precision_value();

		// Calculate expected value using the default currency precision normalization.
		$expected = $precision_value->convert_to_precision(
			Currency::get_currency_precision( 'USD' )
		);

		$this->assertEqualsWithDelta(
			$expected->get(),
			$normalized->get(),
			0.01,
			"Gateway {$gateway_name} should match default normalization when no override is applied."
		);
	}
}

