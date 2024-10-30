<?php

declare( strict_types=1 );

namespace TEC\Tickets\Tests\Unit\Order_Modifiers\Values;

use Codeception\TestCase\WPTestCase;
use InvalidArgumentException;
use stdClass;
use TEC\Tickets\Order_Modifiers\Values\Precision_Value as PV;

class Precision_Value_Test extends WPTestCase {

	/**
	 * @dataProvider get_data_provider
	 * @test
	 */
	public function value_is_returned_correctly( $raw_value, $precision, $expected ) {
		$value = new PV( $raw_value, $precision );
		$this->assertSame( $expected, $value->get() );
	}

	/**
	 * @dataProvider validate_data_provider
	 * @test
	 */
	public function validation_fails_for_invalid_types( $raw_value ) {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Value must be a number.' );
		new PV( $raw_value );
	}

	/**
	 * @test
	 */
	public function validation_fails_for_NAN_constant() {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'NAN is by definition not a number.' );
		new PV( NAN );
	}

	/**
	 * @test
	 */
	public function validation_fails_for_INF_constant() {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Infinity is too big for us to work with.' );
		new PV( INF );
	}

	/**
	 * @test
	 */
	public function precision_can_be_changed() {
		$value = new PV( 1.234 );
		$this->assertEquals( 2, $value->get_precision() );

		// Test that the object is the same when the same precision is set.
		$new_value = $value->convert_to_precision( 2 );
		$this->assertSame( $value, $new_value );

		// Test that the object is different when a different precision is set.
		$new_value = $value->convert_to_precision( 3 );
		$this->assertEquals( 3, $new_value->get_precision() );
		$this->assertNotSame( $value, $new_value );
	}

	/**
	 * @dataProvider addition_data_provider
	 * @test
	 */
	public function addition_works_with_objects( PV $a, PV $b, $expected_sum ) {
		$this->assertEquals( $expected_sum, $a->add( $b )->get() );
	}

	/**
	 * @dataProvider subtraction_data_provider
	 * @test
	 */
	public function subtraction_works_with_objects( PV $a, PV $b, $expected_difference ) {
		$this->assertEquals( $expected_difference, $a->subtract( $b )->get() );
	}


	public function get_data_provider() {
		// raw value, precision, expected value
		yield 'Normal float rounding down' => [ 1.234, 2, 1.23 ];
		yield 'Normal float rounding up' => [ 1.236, 2, 1.24 ];
		yield 'PI rounded to 5 decimal places' => [ pi(), 5, 3.14159 ];

		// Integers to floats.
		yield 'Integer to float with 2 decimal precision' => [ 1, 2, 1.00 ];
		yield 'Integer with no precision' => [ 100, 0, (float) 100 ];

		// Numeric strings.
		yield 'Numeric string to float with 2 decimal precision' => [ '1.234', 2, 1.23 ];
		yield 'Numeric string with 4 decimal places' => [ '1.2345', 4, 1.2345 ];

		// Hexadecimal notation.
		yield 'Hexadecimal notation to float' => [ 0x539, 2, 1337.00 ];

		// Binary notation.
		yield 'Binary notation to float with 2 decimal precision' => [ 0b10100111001, 2, 1337.00 ];
		yield 'Binary notation to float with no precision' => [ 0b10100111001, 0, (float) 1337 ];

		// Octal notation.
		yield 'Octal notation to float with 4 decimal places' => [ 02471, 4, 1337.0000 ];

		// Underscores in numbers.
		yield 'Number with underscores and no precision' => [ 1_234_567, 0, (float) 1234567 ];
		yield 'Number with underscores and 2 decimal precision' => [ 1_234, 2, 1234.00 ];
	}

	public function validate_data_provider() {
		// Invalid data cases for validation
		yield 'String "foo"' => [ 'foo' ];
		yield 'Alphanumeric string "abc123"' => [ 'abc123' ];
		yield 'Empty array' => [ [] ];
		yield 'Empty object' => [ new stdClass() ];
		yield 'Null value' => [ null ];
		yield 'Boolean true' => [ true ];
		yield 'Boolean false' => [ false ];
	}

	public function addition_data_provider() {
		// Test cases for adding two PV (present value) objects together
		yield 'Simple addition of two PV values' => [ new PV( 1.23 ), new PV( 2.34 ), 3.57 ];
		yield 'Addition with custom precision on second PV' => [ new PV( 1.23 ), new PV( 2.345, 3 ), 3.575 ];
		yield 'Addition with custom precision of 4 decimal places' => [ new PV( 1.23 ), new PV( 2.34, 4 ), 3.5700 ];
		yield 'Addition of positive and negative values' => [ new PV( 3.57 ), new PV( -2.34 ), 1.23 ];
		yield 'Addition of binary values' => [ new PV( 0b10100111001 ), new PV( 0b10100111001 ), 2674.00 ];
		yield 'Small decimals addition' => [ new PV( .05 ), new PV( .01 ), 0.06 ];
		yield 'Addition of two equal small decimal values' => [ new PV( .05 ), new PV( .05 ), 0.10 ];
		yield 'Addition with floating-point precision' => [ new PV( 0.1 ), new PV( 0.2 ), 0.3 ];
		yield 'Addition of zero values' => [ new PV( 0.0 ), new PV( 0.0 ), 0.00 ];
		yield 'Addition of 0.9 and 0.1' => [ new PV( 0.9 ), new PV( 0.1 ), 1.00 ];
		yield 'Addition with very small values and 6 decimal precision' => [ new PV( 0.000009, 6 ), new PV( 0.000001, 6 ), 0.000010 ];
		yield 'Addition of two negative values' => [ new PV( -1.2 ), new PV( 1.2 ), 0.00 ];
		yield 'Negative values addition' => [ new PV( -1.21 ), new PV( -1.21 ), -2.42 ];
	}

	public function subtraction_data_provider() {
		// Test cases for subtracting two PV (present value) objects
		yield 'Simple subtraction of two PV values' => [ new PV( 2.34 ), new PV( 1.23 ), 1.11 ];
		yield 'Subtraction resulting in zero' => [ new PV( 3.57 ), new PV( 3.57 ), 0.00 ];
		yield 'Subtraction with custom precision on second PV' => [ new PV( 3.35 ), new PV( 1.234, 3 ), 2.116 ];
		yield 'Subtraction of negative value from positive value' => [ new PV( 3.57 ), new PV( -2.34 ), 5.91 ];
		yield 'Subtraction of positive value from negative value' => [ new PV( -1.23 ), new PV( 2.34 ), -3.57 ];
		yield 'Subtraction of binary values' => [ new PV( 0b10100111001 ), new PV( 0b10100111001 ), 0.00 ];
		yield 'Subtraction of small decimal values' => [ new PV( 0.06 ), new PV( 0.05 ), 0.01 ];
		yield 'Subtraction resulting in negative value' => [ new PV( 0.05 ), new PV( 0.10 ), -0.05 ];
		yield 'Subtraction with zero as the second value' => [ new PV( 1.23 ), new PV( 0.00 ), 1.23 ];
		yield 'Subtraction of zero from zero' => [ new PV( 0.0 ), new PV( 0.0 ), 0.00 ];
		yield 'Subtraction of two negative values' => [ new PV( -1.21 ), new PV( -1.2 ), -0.01 ];
		yield 'Subtraction of very small values with 6 decimal precision' => [ new PV( 0.000010, 6 ), new PV( 0.000001, 6 ), 0.000009 ];
	}
}