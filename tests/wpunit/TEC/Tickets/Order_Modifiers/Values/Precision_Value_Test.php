<?php

declare( strict_types=1 );

namespace TEC\Tickets\Tests\Unit\Order_Modifiers\Values;

use Codeception\TestCase\WPTestCase;
use InvalidArgumentException;
use stdClass;
use TEC\Tickets\Order_Modifiers\Values\Positive_Integer_Value;
use TEC\Tickets\Order_Modifiers\Values\Precision_Value as PV;

class Precision_Value_Test extends WPTestCase {

	/**
	 * @dataProvider get_data_provider
	 * @test
	 */
	public function value_is_returned_correctly( $raw_value, $precision, $expected ) {
		$value = new PV( $raw_value, new Positive_Integer_Value( $precision ) );
		$this->assertSame( $expected, $value->get() );
	}

	/**
	 * @dataProvider validate_data_provider
	 * @test
	 */
	public function validation_fails_for_invalid_types( $raw_value ) {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Value must be numeric.' );
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
	public function precision_value_object_is_cloned() {
		$precision = new Positive_Integer_Value( 2 );
		$value     = new PV( 1.23, $precision );
		$this->assertNotSame( $precision, $value->get_precision() );
	}

	/**
	 * @test
	 */
	public function precision_can_be_changed() {
		$value = new PV( 1.234 );
		$this->assertEquals( 2, $value->get_precision()->get() );

		// Test that the object is the same when the same precision is set.
		$new_value = $value->convert_to_precision( new Positive_Integer_Value( 2 ) );
		$this->assertSame( $value, $new_value );

		// Test that the object is different when a different precision is set.
		$new_value = $value->convert_to_precision( new Positive_Integer_Value( 3 ) );
		$this->assertEquals( 3, $new_value->get_precision()->get() );
		$this->assertNotSame( $value, $new_value );
	}

	/**
	 * @dataProvider addition_data_provider
	 * @test
	 */
	public function addition_works_with_objects( PV $a, PV $b, $expected_sum ) {
		$this->assertEquals( $expected_sum, $a->add( $b )->get() );
	}

	public function get_data_provider() {
		// raw value, precision, expected value
		return [
			// Normal floats.
			[ 1.234, 2, 1.23 ],
			[ 1.236, 2, 1.24 ],
			[ pi(), 5, 3.14159 ],

			// Integers to floats.
			[ 1, 2, 1.00 ],
			[ 100, 0, (float) 100 ],

			// Numeric strings.
			[ '1.234', 2, 1.23 ],
			[ '1.2345', 4, 1.2345 ],

			// Hexadecimal notation.
			[ 0x539, 2, 1337.00 ],

			// Binary notation.
			[ 0b10100111001, 2, 1337.00 ],
			[ 0b10100111001, 0, (float) 1337 ],

			// Octal notation.
			[ 02471, 4, 1337.0000 ],

			// Underscores in numbers.
			[ 1_234_567, 0, (float) 1234567 ],
			[ 1_234, 2, 1234.00 ],
		];
	}

	public function validate_data_provider() {
		return [
			[ 'foo' ],
			[ 'abc123' ],
			[ [] ],
			[ new stdClass() ],
			[ null ],
			[ true ],
			[ false ],
		];
	}

	public function addition_data_provider() {
		return [
			[ new PV( 1.23 ), new PV( 2.34 ), 3.57 ],
			[ new PV( 1.23 ), new PV( 2.345, new Positive_Integer_Value( 3 ) ), 3.575 ],
			[ new PV( 1.23 ), new PV( 2.34, new Positive_Integer_Value( 4 ) ), 3.5700 ],
			[ new PV( 3.57 ), new PV( -2.34 ), 1.23 ],
			[ new PV( 0b10100111001 ), new PV( 0b10100111001 ), 2674.00 ],
			[ new PV( .05 ), new PV( .01 ), 0.06 ],
			[ new PV( .05 ), new PV( .05 ), 0.10 ],
			[ new PV( 0.1 ), new PV( 0.2 ), 0.3 ],
			[ new PV( 0.0 ), new PV( 0.0 ), 0.00 ],
			[ new PV( 0.9 ), new PV( 0.1 ), 1.00 ],
			[ new PV( 0.000009, new Positive_Integer_Value( 6 ) ), new PV( 0.000001, new Positive_Integer_Value( 6 ) ), 0.000010 ],
			[ new PV( -1.2 ), new PV( 1.2 ), 0.00 ],
			[ new PV( -1.21 ), new PV( -1.21 ), -2.42 ],
		];
	}
}
