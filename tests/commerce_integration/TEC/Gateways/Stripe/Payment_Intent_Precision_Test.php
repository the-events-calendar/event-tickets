<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use Generator;
use TEC\Tickets\Commerce\Utils\Value;
use TEC\Tickets\Commerce\Utils\Currency;
use Tribe\Tests\Traits\With_Uopz;
use Codeception\TestCase\WPTestCase;

/**
 * Integration tests for Payment_Intent with Stripe-specific currency formatting.
 *
 * @since TBD
 */
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
	 * Data provider for Stripe zero-decimal currencies.
	 *
	 * @since TBD
	 *
	 * @return Generator
	 */
	public function stripe_zero_decimal_currencies_provider() {
		$zero_decimal_currencies = [
			'BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG',
			'RWF', 'VND', 'VUV', 'XAF', 'XOF', 'XPF'
		];

		foreach ( $zero_decimal_currencies as $currency ) {
			yield "zero_decimal_{$currency}" => [
				'currency_code' => $currency,
				'input_value' => 5.0,
				'expected_amount' => '5',
				'expected_currency' => $currency,
				'description' => "Zero-decimal currency {$currency} should send raw value to Stripe"
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
	public function stripe_special_case_currencies_provider() {
		return [
			'ISK' => [
				'currency_code' => 'ISK',
				'input_value' => 5.0,
				'expected_amount' => '500',
				'expected_currency' => 'ISK',
				'description' => 'ISK should use 2 decimals for backwards compatibility'
			],
			'HUF' => [
				'currency_code' => 'HUF',
				'input_value' => 10.45,
				'expected_amount' => '10',
				'expected_currency' => 'HUF',
				'description' => 'HUF should use 0 decimals for payouts'
			],
			'TWD' => [
				'currency_code' => 'TWD',
				'input_value' => 800.45,
				'expected_amount' => '800',
				'expected_currency' => 'TWD',
				'description' => 'TWD should use 0 decimals for payouts'
			],
			'UGX' => [
				'currency_code' => 'UGX',
				'input_value' => 5.0,
				'expected_amount' => '500',
				'expected_currency' => 'UGX',
				'description' => 'UGX should use 2 decimals for backwards compatibility'
			],
		];
	}

	/**
	 * Data provider for standard two-decimal currencies.
	 *
	 * @since TBD
	 *
	 * @return Generator
	 */
	public function stripe_two_decimal_currencies_provider() {
		$two_decimal_currencies = [
			'USD', 'EUR', 'GBP', 'CAD', 'AUD', 'CHF', 'SEK', 'NOK', 'DKK', 'PLN', 'CZK', 'BRL', 'ILS'
		];

		foreach ( $two_decimal_currencies as $currency ) {
			yield "two_decimal_{$currency}" => [
				'currency_code' => $currency,
				'input_value' => 5.0,
				'expected_amount' => '500',
				'expected_currency' => $currency,
				'description' => "Two-decimal currency {$currency} should send value × 100 to Stripe"
			];
		}
	}

	/**
	 * Test that zero-decimal currencies are handled correctly by Stripe.
	 *
	 * @since TBD
	 *
	 * @test
	 * @dataProvider stripe_zero_decimal_currencies_provider
	 */
	public function payment_intent_handles_zero_decimal_currencies( $currency_code, $input_value, $expected_amount, $expected_currency, $description ) {
		// Set currency for this test.
		tribe_update_option( Currency::$currency_code_option, $currency_code );

		// Create a Value object - it will get the currency's natural precision.
		$value = new Value( $input_value );

		// Call Payment_Intent::create() which now uses Gateway_Value_Formatter.
		$result = Payment_Intent::create( $value );

		// Assert the Stripe API receives the correct amount.
		$this->assertEquals( $expected_amount, $result['amount'], $description );
		$this->assertEquals( $expected_currency, $result['currency'], "Currency should be {$expected_currency}" );
	}

	/**
	 * Test that special case currencies are handled correctly by Stripe.
	 *
	 * @since TBD
	 *
	 * @test
	 * @dataProvider stripe_special_case_currencies_provider
	 */
	public function payment_intent_handles_special_case_currencies( $currency_code, $input_value, $expected_amount, $expected_currency, $description ) {
		// Set currency for this test.
		tribe_update_option( Currency::$currency_code_option, $currency_code );

		// Create a Value object - it will get the currency's natural precision.
		$value = new Value( $input_value );

		// Call Payment_Intent::create() which now uses Gateway_Value_Formatter.
		$result = Payment_Intent::create( $value );

		// Assert the Stripe API receives the correct amount.
		$this->assertEquals( $expected_amount, $result['amount'], $description );
		$this->assertEquals( $expected_currency, $result['currency'], "Currency should be {$expected_currency}" );
	}

	/**
	 * Test that standard two-decimal currencies are handled correctly by Stripe.
	 *
	 * @since TBD
	 *
	 * @test
	 * @dataProvider stripe_two_decimal_currencies_provider
	 */
	public function payment_intent_handles_two_decimal_currencies( $currency_code, $input_value, $expected_amount, $expected_currency, $description ) {
		// Set currency for this test.
		tribe_update_option( Currency::$currency_code_option, $currency_code );

		// Create a Value object - it will get the currency's natural precision.
		$value = new Value( $input_value );

		// Call Payment_Intent::create() which now uses Gateway_Value_Formatter.
		$result = Payment_Intent::create( $value );

		// Assert the Stripe API receives the correct amount.
		$this->assertEquals( $expected_amount, $result['amount'], $description );
		$this->assertEquals( $expected_currency, $result['currency'], "Currency should be {$expected_currency}" );
	}

	/**
	 * Test that the Stripe hook system can be overridden via filters.
	 *
	 * @since TBD
	 *
	 * @test
	 */
	public function payment_intent_respects_stripe_currency_filter_overrides() {
		// Set currency to JPY (normally zero-decimal).
		tribe_update_option( Currency::$currency_code_option, 'JPY' );

		// Add a filter to override JPY to use 1 decimal instead of 0.
		add_filter( 'tec_tickets_commerce_gateway_value_formatter_stripe_currency_map', function( $currency_data, $currency_code, $gateway ) {
			if ( $currency_code === 'JPY' && $gateway === 'stripe' ) {
				$currency_data['decimal_precision'] = 1; // Override to 1 decimal
			}
			return $currency_data;
		}, 10, 3 );

		// Create a Value object for 5.0 JPY.
		$value = new Value( 5.0 );

		// Call Payment_Intent::create().
		$result = Payment_Intent::create( $value );

		// Assert the filter override worked (5.0 with precision 1 = 50).
		$this->assertEquals( '50', $result['amount'], 'Filter override should change JPY to 1 decimal precision' );
		$this->assertEquals( 'JPY', $result['currency'], 'Currency should remain JPY' );

		// Clean up the filter.
		remove_all_filters( 'tec_tickets_commerce_gateway_value_formatter_stripe_currency_map' );
	}

	/**
	 * Test that application fees are also formatted correctly for Stripe.
	 *
	 * @since TBD
	 *
	 * @test
	 */
	public function payment_intent_application_fee_uses_stripe_formatting() {
		// Set currency to JPY (zero-decimal).
		tribe_update_option( Currency::$currency_code_option, 'JPY' );

		// Create a Value object for 100 JPY.
		$value = new Value( 100.0 );

		// Call Payment_Intent::create().
		$result = Payment_Intent::create( $value );

		// Verify both amount and application_fee_amount are formatted correctly for Stripe.
		$this->assertEquals( '100', $result['amount'], 'JPY amount should be 100 (no decimal places)' );
		$this->assertEquals( '2', $result['application_fee_amount'], 'JPY application fee should be 2 (2% of 100, no decimal places)' );
		$this->assertEquals( 'JPY', $result['currency'], 'Currency should be JPY' );
	}

	/**
	 * Test that the integration works with real-world currency scenarios.
	 *
	 * @since TBD
	 *
	 * @test
	 */
	public function payment_intent_handles_real_world_scenarios() {
		// Test USD: $4.50 should become 450 cents.
		tribe_update_option( Currency::$currency_code_option, 'USD' );
		$usd_value = new Value( 4.50 );
		$usd_result = Payment_Intent::create( $usd_value );
		$this->assertEquals( '450', $usd_result['amount'], 'USD $4.50 should become 450 cents' );
		$this->assertEquals( 'USD', $usd_result['currency'], 'Currency should be USD' );

		// Test JPY: ¥500 should become 500 (no multiplication).
		tribe_update_option( Currency::$currency_code_option, 'JPY' );
		$jpy_value = new Value( 500.0 );
		$jpy_result = Payment_Intent::create( $jpy_value );
		$this->assertEquals( '500', $jpy_result['amount'], 'JPY ¥500 should become 500 (no decimal places)' );
		$this->assertEquals( 'JPY', $jpy_result['currency'], 'Currency should be JPY' );

		// Test EUR: €12.99 should become 1299 cents.
		tribe_update_option( Currency::$currency_code_option, 'EUR' );
		$eur_value = new Value( 12.99 );
		$eur_result = Payment_Intent::create( $eur_value );
		$this->assertEquals( '1299', $eur_result['amount'], 'EUR €12.99 should become 1299 cents' );
		$this->assertEquals( 'EUR', $eur_result['currency'], 'Currency should be EUR' );
	}

	/**
	 * Test that the integration handles edge cases properly.
	 *
	 * @since TBD
	 *
	 * @test
	 */
	public function payment_intent_handles_edge_cases() {
		// Test with zero value.
		tribe_update_option( Currency::$currency_code_option, 'USD' );
		$zero_value = new Value( 0.0 );
		$zero_result = Payment_Intent::create( $zero_value );
		$this->assertEquals( '0', $zero_result['amount'], 'Zero value should remain zero' );
		$this->assertEquals( '0', $zero_result['application_fee_amount'], 'Zero application fee should remain zero' );

		// Test with small value that will produce a meaningful application fee.
		$small_value = new Value( 0.50 ); // 50 cents
		$small_result = Payment_Intent::create( $small_value );
		$this->assertEquals( '50', $small_result['amount'], 'Small value should be normalized correctly' );
		$this->assertEquals( '1', $small_result['application_fee_amount'], 'Small application fee should be normalized correctly (2% of 50 cents = 1 cent)' );
	}
}
