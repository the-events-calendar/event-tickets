<?php

declare( strict_types=1 );

namespace TEC\Tickets\Tests\Unit\Order_Modifiers\Values;

use Codeception\TestCase\WPTestCase;
use InvalidArgumentException;
use TEC\Tickets\Order_Modifiers\Values\Percent_Value;

class Percent_Value_Test extends WPTestCase {

	/**
	 * @test
	 */
	public function from_number_throws_exception_for_non_numeric_value() {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Value must be a number.' );

		new Percent_Value( 'foo' );
	}

	/**
	 * @test
	 */
	public function from_number_throws_exception_for_nan() {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'NAN is by definition not a number.' );

		new Percent_Value( NAN );
	}

	/**
	 * @test
	 */
	public function from_number_throws_exception_for_inf() {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Infinity is too big for us to work with.' );

		new Percent_Value( INF );
	}

	/**
	 * @test
	 * @dataProvider percent_data_provider
	 */
	public function it_should_get_percents_correctly( $raw_value, $expected ) {
		$value = new Percent_Value( $raw_value );
		$this->assertSame( $expected, $value->get_as_percent() );
	}

	/**
	 * @test
	 * @dataProvider decimal_data_provider
	 */
	public function it_should_get_decimals_correctly( $raw_value, $expected ) {
		$value = new Percent_Value( $raw_value );
		$this->assertSame( $expected, $value->get_as_decimal() );
	}

	public function percent_data_provider() {
		return [
			[ 10, (float) 10 ],
			[ 5, (float) 5 ],
			[ 0.5, 0.5 ],
			[ 0.05, 0.05 ],
		];
	}

	public function decimal_data_provider() {
		return [
			[ 10, 0.1 ],
			[ 5, 0.05 ],
			[ 0.5, 0.005 ],
			[ 0.05, 0.0005 ],
		];
	}
}
