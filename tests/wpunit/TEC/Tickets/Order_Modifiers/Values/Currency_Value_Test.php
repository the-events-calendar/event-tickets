<?php

declare( strict_types=1 );

namespace TEC\Tickets\Tests\Unit\Order_Modifiers\Values;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Values\Currency_Value;
use TEC\Tickets\Commerce\Values\Precision_Value;

class Currency_Value_Test extends WPTestCase {

	/**
	 * @test
	 */
	public function default_values_used_with_create_formatted_correctly() {
		Currency_Value::set_defaults(
			'¢',
			'.',
			',',
			'after'
		);

		$currency_value = Currency_Value::create( new Precision_Value( 100 ) );
		$this->assertEquals( '100,00¢', $currency_value->get() );
		$this->assertEquals( '100,00¢', (string) $currency_value );

		$currency_value = Currency_Value::create( new Precision_Value( 1000 ) );
		$this->assertEquals( '1.000,00¢', $currency_value->get() );
		$this->assertEquals( '1.000,00¢', (string) $currency_value );

		Currency_Value::set_defaults();

		$currency_value = Currency_Value::create( new Precision_Value( 100 ) );
		$this->assertEquals( '$100.00', $currency_value->get() );
		$this->assertEquals( '$100.00', (string) $currency_value );

		$currency_value = Currency_Value::create( new Precision_Value( 1000 ) );
		$this->assertEquals( '$1,000.00', $currency_value->get() );
		$this->assertEquals( '$1,000.00', (string) $currency_value );
	}

	/**
	 * @test
	 * @dataProvider format_values_data_provider
	 */
	public function it_should_properly_format_values( array $constructor_args, string $expected ) {
		$currency_value = Currency_Value::create( ...$constructor_args );
		$this->assertEquals( $expected, $currency_value->get() );
		$this->assertEquals( $expected, (string) $currency_value );
	}

	public function format_values_data_provider() {
		yield 'default values' => [
			[ new Precision_Value( 100 ) ],
			'$100.00',
		];

		yield 'custom values' => [
			[ new Precision_Value( 100 ), '€', '.', ',', 'before' ],
			'€100,00',
		];

		yield 'custom values with different thousands' => [
			[ new Precision_Value( 1000 ), '€', '.', ',', 'after' ],
			'1.000,00€',
		];

		yield 'negative value' => [
			[ new Precision_Value( -100 ) ],
			'- $100.00',
		];

		yield 'value with different precision' => [
			[ new Precision_Value( 100, 0 ), '¢', null, null, 'after' ],
			'100¢',
		];
	}
}
