<?php

declare( strict_types=1 );

namespace TEC\Tickets\Tests\Unit\Order_Modifiers\Values;

use Codeception\TestCase\WPTestCase;
use InvalidArgumentException;
use TEC\Tickets\Commerce\Order_Modifiers\Values\Percent_Value as Percent;
use TEC\Tickets\Commerce\Order_Modifiers\Values\Precision_Value as PV;

class Percent_Value_Test extends WPTestCase {

	/**
	 * @test
	 * @dataProvider invalid_data_provider
	 */
	public function it_throws_exception_for_invalid_values( $raw_value, string $expected_message ) {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( $expected_message );

		new Percent( $raw_value );
	}

	/**
	 * @test
	 * @dataProvider percent_data_provider
	 */
	public function it_should_get_percents_correctly( $raw_value, float $expected ) {
		$value = new Percent( $raw_value );
		$this->assertSame( $expected, $value->get_as_percent() );
	}

	/**
	 * @test
	 * @dataProvider decimal_data_provider
	 */
	public function it_should_get_decimals_correctly( $raw_value, float $expected ) {
		$value = new Percent( $raw_value );
		$this->assertSame( $expected, $value->get_as_decimal() );
	}

	/**
	 * @test
	 * @dataProvider multiplication_data_provider
	 */
	public function it_should_multiply_objects_correctly( $raw_value, PV $multiplier, $expected ) {
		$value = new Percent( $raw_value );
		$result = $multiplier->multiply( $value );

		$this->assertSame( $expected, (string) $result );
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

	public function multiplication_data_provider() {
		$multiplier = new PV( 100 );

		// Normal cases
		yield '10 percent * 100' => [ 10, $multiplier, '10.00' ];
		yield '5 percent * 100' => [ 5, $multiplier, '5.00' ];
		yield 'Half percent * 100' => [ 0.5, $multiplier, '0.50' ];
		yield 'Tiny percent * 100' => [ 0.05, $multiplier, '0.05' ];

		// Edge cases
		yield 'One hundred percent * 100' => [ 100, $multiplier, '100.00' ];
		yield 'Large percent * 100' => [ 10000, $multiplier, '10000.00' ];
		yield 'Negative percent * 100' => [ -50, $multiplier, '-50.00' ];

		// Cases with a different multiplier.
		yield '25 percent of 50' => [ 25, new PV( 50 ), '12.50' ];
		yield '17 percent of 1000' => [ 17, new PV( 1000 ), '170.00' ];
		yield '1.5 percent of 10' => [ 1.5, new PV( 10 ), '0.15' ];
	}
}
