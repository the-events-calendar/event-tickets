<?php

declare( strict_types=1 );

namespace TEC\Tickets\Tests\Unit\Order_Modifiers\Values;

use Codeception\TestCase\WPTestCase;
use InvalidArgumentException;
use stdClass;
use TEC\Tickets\Order_Modifiers\Values\Positive_Integer_Value;
use TEC\Tickets\Order_Modifiers\Values\Precision_Value;

class Precision_Value_Test extends WPTestCase {

	/**
	 * Test get.
	 *
	 * @dataProvider get_data_provider
	 * @test
	 */
	public function value_is_returned_correctly( $raw_value, $precision, $expected ) {
		$value = new Precision_Value( $raw_value, new Positive_Integer_Value( $precision ) );
		$this->assertSame( $expected, $value->get() );
	}

	/**
	 * Test validate.
	 *
	 * @dataProvider validate_data_provider
	 * @test
	 */
	public function validation_fails_for_invalid_types( $raw_value ) {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Value must be numeric.' );
		new Precision_Value( $raw_value );
	}

	public function get_data_provider() {
		// raw value, precision, expected value
		return [
			[ 1.234, 2, 1.23 ],
			[ 1.236, 2, 1.24 ],
			[ pi(), 5, 3.14159 ],
			[ 1, 2, 1.00 ],
			[ '1.234', 2, 1.23 ],
			[ 100, 0, (float) 100 ],
		];
	}

	public function validate_data_provider() {
		return [
			[ 'foo' ],
			[ [] ],
			[ new stdClass() ],
			[ 'abc123' ],
			[ null ],
			[ NAN ],
		];
	}
}
