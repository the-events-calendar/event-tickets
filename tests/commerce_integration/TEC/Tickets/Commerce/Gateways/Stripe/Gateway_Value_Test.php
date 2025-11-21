<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use Codeception\TestCase\WPTestCase;
use Generator;
use TEC\Tickets\Commerce\Gateways\Stripe\Gateway as Stripe_Gateway;
use TEC\Tickets\Commerce\Utils\Currency;
use TEC\Tickets\Commerce\Values\Gateway_Value;
use TEC\Tickets\Commerce\Values\Precision_Value;

/**
 * Test suite for Stripe Gateway_Value normalization.
 *
 * Tests the Gateway_Value class with Stripe's custom normalize_value_for_gateway
 * implementation, which handles Stripe's special currency precision requirements.
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
	 * Data provider for Stripe zero-decimal currencies.
	 *
	 * @since TBD
	 *
	 * @return Generator
	 */
	public function stripe_zero_decimal_currencies_provider(): Generator {
		$zero_decimal_currencies = [
			'BIF',
			'CLP',
			'DJF',
			'GNF',
			'JPY',
			'KMF',
			'KRW',
			'MGA',
			'PYG',
			'RWF',
			'VND',
			'VUV',
			'XAF',
			'XOF',
			'XPF',
		];

		foreach ( $zero_decimal_currencies as $currency_code ) {
			yield sprintf( '%s_zero_decimal', $currency_code ) => [
				'currency_code'      => $currency_code,
				'input_value'         => 500.0,
				'expected_precision'  => 0,
				'expected_float'      => 500.0,
				'expected_integer'    => 500,
			];
		}
	}

	/**
	 * Data provider for Stripe special case currencies.
	 *
	 * @since TBD
	 *
	 * @return Generator
	 */
	public function stripe_special_case_currencies_provider(): Generator {
		$special_cases = [
			'ISK' => [
				'currency_code'      => 'ISK',
				'input_value'        => 5.0,
				'expected_precision' => 2,
				'expected_float'     => 5.0,
				'expected_integer'   => 500,
				'description'        => 'ISK should use 2 decimals for backwards compatibility',
			],
			'HUF' => [
				'currency_code'      => 'HUF',
				'input_value'        => 10.45,
				'expected_precision' => 0,
				'expected_float'     => 10.0,
				'expected_integer'   => 10,
				'description'        => 'HUF should use 0 decimals for payouts',
			],
			'TWD' => [
				'currency_code'      => 'TWD',
				'input_value'        => 800.45,
				'expected_precision' => 0,
				'expected_float'     => 800.0,
				'expected_integer'   => 800,
				'description'        => 'TWD should use 0 decimals for payouts',
			],
			'UGX' => [
				'currency_code'      => 'UGX',
				'input_value'        => 5.0,
				'expected_precision' => 2,
				'expected_float'     => 5.0,
				'expected_integer'   => 500,
				'description'        => 'UGX should use 2 decimals for backwards compatibility',
			],
		];

		foreach ( $special_cases as $data ) {
			yield sprintf( '%s_special_case', $data['currency_code'] ) => $data;
		}
	}

	/**
	 * Data provider for standard currencies that use default precision.
	 *
	 * @since TBD
	 *
	 * @return Generator
	 */
	public function stripe_default_precision_currencies_provider(): Generator {
		$standard_currencies = [
			'USD',
			'EUR',
			'GBP',
			'CAD',
			'AUD',
			'CHF',
			'SEK',
			'NOK',
			'DKK',
			'PLN',
			'CZK',
			'BRL',
			'ILS',
		];

		foreach ( $standard_currencies as $currency_code ) {
			$precision = (int) Currency::get_currency_precision( $currency_code );
			$base = 10.0;

			yield sprintf( '%s_default', $currency_code ) => [
				'currency_code'      => $currency_code,
				'input_value'         => $base,
				'expected_precision'  => $precision,
				'expected_float'      => $base,
				'expected_integer'    => (int) ( $base * ( 10 ** $precision ) ),
			];
		}
	}

	/**
	 * Test that zero-decimal currencies are normalized correctly for Stripe.
	 *
	 * @since TBD
	 *
	 * @test
	 * @dataProvider stripe_zero_decimal_currencies_provider
	 */
	public function it_should_normalize_zero_decimal_currencies_for_stripe( $currency_code, $input_value, $expected_precision, $expected_float, $expected_integer ) {
		$gateway = tribe( Stripe_Gateway::class );
		$precision_value = new Precision_Value( $input_value, 2 ); // Start with 2 decimal precision.
		$gateway_value = new Gateway_Value( $gateway, $precision_value, $currency_code );

		$actual_float = $gateway_value->get();
		$actual_integer = $gateway_value->get_integer();

		$this->assertIsFloat( $actual_float, "Float value should be returned for {$currency_code}." );
		$this->assertIsInt( $actual_integer, "Integer value should be returned for {$currency_code}." );
		$this->assertEqualsWithDelta( $expected_float, $actual_float, 0.01, "{$currency_code} should be normalized to 0 decimal precision." );
		$this->assertEquals( $expected_integer, $actual_integer, "{$currency_code} integer value should equal float value (zero-decimal currency)." );
	}

	/**
	 * Test that special case currencies are normalized correctly for Stripe.
	 *
	 * @since TBD
	 *
	 * @test
	 * @dataProvider stripe_special_case_currencies_provider
	 */
	public function it_should_normalize_special_case_currencies_for_stripe( $currency_code, $input_value, $expected_precision, $expected_float, $expected_integer, $description ) {
		$gateway = tribe( Stripe_Gateway::class );
		$precision_value = new Precision_Value( $input_value, 2 ); // Start with 2 decimal precision.
		$gateway_value = new Gateway_Value( $gateway, $precision_value, $currency_code );

		$actual_float = $gateway_value->get();
		$actual_integer = $gateway_value->get_integer();

		$this->assertIsFloat( $actual_float, "Float value should be returned for {$currency_code}." );
		$this->assertIsInt( $actual_integer, "Integer value should be returned for {$currency_code}." );
		$this->assertEqualsWithDelta( $expected_float, $actual_float, 0.01, $description );
		$this->assertEquals( $expected_integer, $actual_integer, "{$currency_code} integer should match expected value." );
	}

	/**
	 * Test that standard currencies use default precision for Stripe.
	 *
	 * @since TBD
	 *
	 * @test
	 * @dataProvider stripe_default_precision_currencies_provider
	 */
	public function it_should_normalize_default_precision_currencies_for_stripe( $currency_code, $input_value, $expected_precision, $expected_float, $expected_integer ) {
		$gateway = tribe( Stripe_Gateway::class );
		$precision_value = new Precision_Value( $input_value, $expected_precision );
		$gateway_value = new Gateway_Value( $gateway, $precision_value, $currency_code );

		$actual_float = $gateway_value->get();
		$actual_integer = $gateway_value->get_integer();

		$this->assertIsFloat( $actual_float, "Float value should be returned for {$currency_code}." );
		$this->assertIsInt( $actual_integer, "Integer value should be returned for {$currency_code}." );
		$this->assertEqualsWithDelta( $expected_float, $actual_float, 0.01, "{$currency_code} should use default precision." );
		$this->assertEquals( $expected_integer, $actual_integer, "{$currency_code} integer value should match expected." );
	}

	/**
	 * Test that special cases take precedence over zero-decimal list.
	 *
	 * @since TBD
	 *
	 * @test
	 */
	public function it_should_apply_special_cases_before_zero_decimal_check() {
		$gateway = tribe( Stripe_Gateway::class );

		// ISK is normally 0 decimals but Stripe special case makes it 2 decimals.
		$isk_value = new Precision_Value( 100.0, 0 );
		$isk_gateway_value = new Gateway_Value( $gateway, $isk_value, 'ISK' );
		$this->assertEqualsWithDelta( 100.0, $isk_gateway_value->get(), 0.01, 'ISK should use 2 decimals (special case), not 0.' );
		$this->assertEquals( 10000, $isk_gateway_value->get_integer(), 'ISK should be 10000 (100 * 100) for 2 decimal precision.' );

		// HUF should use 0 decimals (special case).
		$huf_value = new Precision_Value( 1000.0, 2 );
		$huf_gateway_value = new Gateway_Value( $gateway, $huf_value, 'HUF' );
		$this->assertEqualsWithDelta( 1000.0, $huf_gateway_value->get(), 0.01, 'HUF should use 0 decimals (special case).' );
		$this->assertEquals( 1000, $huf_gateway_value->get_integer(), 'HUF should be 1000 (0 decimal precision).' );
	}

	/**
	 * Test that JPY (zero-decimal) is normalized correctly.
	 *
	 * @since TBD
	 *
	 * @test
	 */
	public function it_should_normalize_jpy_as_zero_decimal() {
		$gateway = tribe( Stripe_Gateway::class );

		// Test with JPY which should be zero-decimal.
		$jpy_value = new Precision_Value( 5000.0, 2 );
		$jpy_gateway_value = new Gateway_Value( $gateway, $jpy_value, 'JPY' );

		$this->assertEqualsWithDelta( 5000.0, $jpy_gateway_value->get(), 0.01, 'JPY should be normalized to 0 decimal precision.' );
		$this->assertEquals( 5000, $jpy_gateway_value->get_integer(), 'JPY integer should equal float (zero-decimal).' );
	}

	/**
	 * Test that USD (standard 2 decimal) works correctly.
	 *
	 * @since TBD
	 *
	 * @test
	 */
	public function it_should_normalize_usd_with_two_decimals() {
		$gateway = tribe( Stripe_Gateway::class );

		$usd_value = new Precision_Value( 10.50, 2 );
		$usd_gateway_value = new Gateway_Value( $gateway, $usd_value, 'USD' );

		$this->assertEqualsWithDelta( 10.50, $usd_gateway_value->get(), 0.01, 'USD should maintain 2 decimal precision.' );
		$this->assertEquals( 1050, $usd_gateway_value->get_integer(), 'USD should be 1050 (10.50 * 100).' );
	}

	/**
	 * Test that currency code normalization works (uppercase).
	 *
	 * @since TBD
	 *
	 * @test
	 */
	public function it_should_normalize_currency_code_to_uppercase() {
		$gateway = tribe( Stripe_Gateway::class );
		$precision_value = new Precision_Value( 10.50, 2 );

		// Test with lowercase.
		$gateway_value_lower = new Gateway_Value( $gateway, $precision_value, 'usd' );
		// Test with uppercase.
		$gateway_value_upper = new Gateway_Value( $gateway, $precision_value, 'USD' );

		$this->assertEqualsWithDelta(
			$gateway_value_lower->get(),
			$gateway_value_upper->get(),
			0.01,
			'Lowercase and uppercase currency codes should produce the same result for Stripe.'
		);
	}

	/**
	 * Test that different input precisions are converted correctly.
	 *
	 * @since TBD
	 *
	 * @test
	 */
	public function it_should_convert_input_precision_to_stripe_precision() {
		$gateway = tribe( Stripe_Gateway::class );

		// JPY with 2 decimal input should be converted to 0 decimal.
		// Using 10.00 to avoid rounding issues (10.50 would round to 11).
		$jpy_value_2dec = new Precision_Value( 10.00, 2 );
		$jpy_gateway_value = new Gateway_Value( $gateway, $jpy_value_2dec, 'JPY' );
		$this->assertEqualsWithDelta( 10.0, $jpy_gateway_value->get(), 0.01, 'JPY should convert from 2 decimal to 0 decimal.' );
		$this->assertEquals( 10, $jpy_gateway_value->get_integer(), 'JPY integer should be 10 (0 decimal).' );

		// Test that 10.50 actually rounds up to 11 when converting to 0 decimal.
		$jpy_value_round = new Precision_Value( 10.50, 2 );
		$jpy_gateway_value_round = new Gateway_Value( $gateway, $jpy_value_round, 'JPY' );
		$this->assertEqualsWithDelta( 11.0, $jpy_gateway_value_round->get(), 0.01, 'JPY 10.50 should round to 11 when converting to 0 decimal.' );
		$this->assertEquals( 11, $jpy_gateway_value_round->get_integer(), 'JPY integer should be 11 (rounded from 10.50).' );

		// ISK with 0 decimal input should be converted to 2 decimal.
		$isk_value_0dec = new Precision_Value( 100.0, 0 );
		$isk_gateway_value = new Gateway_Value( $gateway, $isk_value_0dec, 'ISK' );
		$this->assertEqualsWithDelta( 100.0, $isk_gateway_value->get(), 0.01, 'ISK should convert from 0 decimal to 2 decimal.' );
		$this->assertEquals( 10000, $isk_gateway_value->get_integer(), 'ISK integer should be 10000 (2 decimal).' );
	}

	/**
	 * Test with real-world value scenarios.
	 *
	 * @since TBD
	 *
	 * @test
	 */
	public function it_should_handle_real_world_value_scenarios() {
		$gateway = tribe( Stripe_Gateway::class );

		// USD: $4.50 should become 450 cents.
		$usd_value = new Precision_Value( 4.50, 2 );
		$usd_result = new Gateway_Value( $gateway, $usd_value, 'USD' );
		$this->assertEquals( '450', (string) $usd_result->get_integer(), 'USD $4.50 should become 450 cents.' );

		// JPY: ¥500 should become 500 (no multiplication).
		$jpy_value = new Precision_Value( 500.0, 0 );
		$jpy_result = new Gateway_Value( $gateway, $jpy_value, 'JPY' );
		$this->assertEquals( '500', (string) $jpy_result->get_integer(), 'JPY ¥500 should become 500 (no decimal places).' );

		// EUR: €12.99 should become 1299 cents.
		$eur_value = new Precision_Value( 12.99, 2 );
		$eur_result = new Gateway_Value( $gateway, $eur_value, 'EUR' );
		$this->assertEquals( '1299', (string) $eur_result->get_integer(), 'EUR €12.99 should become 1299 cents.' );
	}
}

