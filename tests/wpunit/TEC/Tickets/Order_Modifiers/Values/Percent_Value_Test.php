<?php

declare( strict_types=1 );

namespace TEC\Tickets\Tests\Unit\Order_Modifiers\Values;

use Codeception\TestCase\WPTestCase;
use InvalidArgumentException;
use TEC\Tickets\Commerce\Order_Modifiers\Values\Percent_Value;

class Percent_Value_Test extends WPTestCase {

	/**
	 * @test
	 * @dataProvider invalid_data_provider
	 */
	public function it_throws_exception_for_invalid_values( $raw_value, string $expected_message ) {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( $expected_message );

		new Percent_Value( $raw_value );
	}

	/**
	 * @test
	 * @dataProvider percent_data_provider
	 */
	public function it_should_get_percents_correctly( $raw_value, float $expected ) {
		$value = new Percent_Value( $raw_value );
		$this->assertSame( $expected, $value->get_as_percent() );
	}

	/**
	 * @test
	 * @dataProvider decimal_data_provider
	 */
	public function it_should_get_decimals_correctly( $raw_value, float $expected ) {
		$value = new Percent_Value( $raw_value );
		$this->assertSame( $expected, $value->get_as_decimal() );
	}

	// Data Providers

	public function invalid_data_provider() {
		yield 'Non-numeric value' => [ 'foo', 'Value must be a number.' ];
		yield 'NAN value' => [ NAN, 'NAN is by definition not a number.' ];
		yield 'Infinity value' => [ INF, 'Infinity is too big for us to work with.' ];
		yield 'Infinity value' => [ 0.0001, 'Percent value cannot be smaller than 0.0001 (0.01%).' ];
	}

	public function percent_data_provider() {
		// Normal cases
		yield '10 percent' => [ 10, (float) 10 ];
		yield '5 percent' => [ 5, (float) 5 ];
		yield 'Half percent' => [ 0.5, 0.5 ];
		yield 'Tiny percent' => [ 0.05, 0.05 ];

		// Edge cases
		yield 'One hundred percent' => [ 100, 100.0 ];
		yield 'Large percent' => [ 10000, 10000.0 ];
		yield 'Negative percent' => [ -50, -50.0 ];
	}

	public function decimal_data_provider() {
		// Normal cases
		yield '10 percent as decimal' => [ 10, 0.1 ];
		yield '5 percent as decimal' => [ 5, 0.05 ];
		yield 'Half percent as decimal' => [ 0.5, 0.005 ];
		yield 'Tiny percent as decimal' => [ 0.05, 0.0005 ];

		// Edge cases
		yield 'One hundred percent as decimal' => [ 100, 1.0 ];
		yield 'Large percent as decimal' => [ 10000, 100.0 ];
		yield 'Negative percent as decimal' => [ -50, -0.5 ];
	}
}
