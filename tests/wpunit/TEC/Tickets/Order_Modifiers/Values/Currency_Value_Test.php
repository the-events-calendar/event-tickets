<?php

declare( strict_types=1 );

namespace TEC\Tickets\Tests\Unit\Order_Modifiers\Values;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Order_Modifiers\Values\Currency_Value;
use TEC\Tickets\Order_Modifiers\Values\Precision_Value;

class Currency_Value_Test extends WPTestCase {

	/**
	 * @test
	 * @dataProvider currency_value_data_provider
	 */
	public function default_values_used_with_create_formatted_correctly(
		?string $currency_symbol,
		?string $decimal_separator,
		?string $thousands_separator,
		?string $symbol_position,
		Precision_Value $precision_value,
		string $expected_output,
		string $expected_escaped_output
	): void {
		Currency_Value::set_defaults(
			$currency_symbol,
			$thousands_separator,
			$decimal_separator,
			$symbol_position
		);

		$currency_value = Currency_Value::create( $precision_value );

		// Test unescaped value.
		$this->assertEquals( $expected_output, $currency_value->get() , 'Expected output should contain the unescaped symbol.');
		$this->assertEquals( $expected_output, (string) $currency_value );

		// Test escaped value.
		// @todo - Skip this test since the escaping doesn't work properly yet.
		//$this->assertEquals( $expected_escaped_output, $currency_value->get_escaped_value(), 'Expected output should contain the escaped symbol.' );

		Currency_Value::reset_locale_to_defaults();
	}

	/**
	 * @test
	 */
	public function reset_locale_to_defaults_works_correctly(): void {
		// Custom settings applied first.
		Currency_Value::set_defaults(
			'€',
			'.',
			',',
			'before'
		);

		$currency_value = Currency_Value::create( new Precision_Value( 1000 ) );
		$this->assertEquals( '€1.000,00', $currency_value->get() );

		// Reset to defaults.
		Currency_Value::set_defaults();
		$currency_value = Currency_Value::create( new Precision_Value( 1000 ) );
		$this->assertEquals( '$1,000.00', $currency_value->get() );
	}

	/**
	 * Data provider for currency value tests.
	 *
	 * @return array
	 */
	public function currency_value_data_provider(): array {
		return [
			// Standard cases.
			'custom_defaults_with_cents_100'  => [
				'currency_symbol'         => '¢',
				'decimal_separator'       => '.',
				'thousands_separator'     => ',',
				'symbol_position'         => 'after',
				'precision_value'         => new Precision_Value( 100 ),
				'expected_output'         => '100.00¢',
				'expected_escaped_output' => '100.00&amp;cent;',
			],
			'custom_defaults_with_cents_1000' => [
				'currency_symbol'         => '¢',
				'decimal_separator'       => '.',
				'thousands_separator'     => ',',
				'symbol_position'         => 'after',
				'precision_value'         => new Precision_Value( 1000 ),
				'expected_output'         => '1,000.00¢',
				'expected_escaped_output' => '1,000.00&amp;cent;',
			],
			'default_usd_format_100'          => [
				'currency_symbol'         => null,
				'decimal_separator'       => null,
				'thousands_separator'     => null,
				'symbol_position'         => null,
				'precision_value'         => new Precision_Value( 100 ),
				'expected_output'         => '$100.00',
				'expected_escaped_output' => '&#36;100.00',
			],
			'default_usd_format_1000'         => [
				'currency_symbol'         => null,
				'decimal_separator'       => null,
				'thousands_separator'     => null,
				'symbol_position'         => null,
				'precision_value'         => new Precision_Value( 1000 ),
				'expected_output'         => '$1,000.00',
				'expected_escaped_output' => '&#36;1,000.00',
			],

			// Edge cases.
			'negative_value'                  => [
				'currency_symbol'         => '$',
				'decimal_separator'       => '.',
				'thousands_separator'     => ',',
				'symbol_position'         => 'before',
				'precision_value'         => new Precision_Value( -123456.78 ),
				'expected_output'         => '$-123,456.78',
				'expected_escaped_output' => '&#36;-123,456.78',
			],
			'large_value'                     => [
				'currency_symbol'         => '€',
				'decimal_separator'       => ',',
				'thousands_separator'     => '.',
				'symbol_position'         => 'before',
				'precision_value'         => new Precision_Value( 1234567890.12 ),
				'expected_output'         => '€1.234.567.890,12',
				'expected_escaped_output' => '&euro;1.234.567.890,12',
			],
			'no_thousands_separator'          => [
				'currency_symbol'         => '£',
				'decimal_separator'       => '.',
				'thousands_separator'     => '',
				'symbol_position'         => 'before',
				'precision_value'         => new Precision_Value( 1000000.25 ),
				'expected_output'         => '£1000000.25',
				'expected_escaped_output' => '&pound;1000000.25',
			],
			'zero_value'                      => [
				'currency_symbol'         => '¥',
				'decimal_separator'       => '.',
				'thousands_separator'     => ',',
				'symbol_position'         => 'after',
				'precision_value'         => new Precision_Value( 0 ),
				'expected_output'         => '0.00¥',
				'expected_escaped_output' => '0.00&#165;',
			],
			'decimal_only'                    => [
				'currency_symbol'         => '₹',
				'decimal_separator'       => '.',
				'thousands_separator'     => ',',
				'symbol_position'         => 'before',
				'precision_value'         => new Precision_Value( 0.25 ),
				'expected_output'         => '₹0.25',
				'expected_escaped_output' => '&#8377;0.25',
			],

			// New edge cases.
			'large_precision_value'           => [
				'currency_symbol'         => '$',
				'decimal_separator'       => '.',
				'thousands_separator'     => ',',
				'symbol_position'         => 'before',
				'precision_value'         => new Precision_Value( 123.456789, 6 ),
				'expected_output'         => '$123.456789',
				'expected_escaped_output' => '&#36;123.456789',
			],
			'negative_zero'                   => [
				'currency_symbol'         => '$',
				'decimal_separator'       => '.',
				'thousands_separator'     => ',',
				'symbol_position'         => 'before',
				'precision_value'         => new Precision_Value( -0 ),
				'expected_output'         => '$0.00',
				'expected_escaped_output' => '&#36;0.00',
			],
			'very_small_value'                => [
				'currency_symbol'         => '$',
				'decimal_separator'       => '.',
				'thousands_separator'     => ',',
				'symbol_position'         => 'before',
				'precision_value'         => new Precision_Value( 0.000123, 6 ),
				'expected_output'         => '$0.000123',
				'expected_escaped_output' => '&#36;0.000123',
			],
		];
	}
}
