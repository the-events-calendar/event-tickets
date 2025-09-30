<?php

namespace TEC\Tickets\Tests\REST\TEC\V1\Parameter_Types;

use TEC\Tickets\REST\TEC\V1\Parameter_Types\Capacity_Integer;
use TEC\Common\REST\TEC\V1\Exceptions\InvalidRestArgumentException;
use Codeception\TestCase\WPTestCase;

/**
 * Test the Capacity_Integer parameter type.
 *
 * @since TBD
 */
class Capacity_Integer_Test extends WPTestCase {

	/**
	 * Test that the parameter type returns the correct type.
	 *
	 * @since TBD
	 */
	public function test_get_type() {
		$parameter = new Capacity_Integer( 'capacity' );
		$this->assertEquals( 'integer', $parameter->get_type() );
	}

	/**
	 * Test that positive integers are validated correctly.
	 *
	 * @since TBD
	 */
	public function test_validate_positive_integers() {
		$parameter = new Capacity_Integer( 'capacity' );
		$validator = $parameter->get_validator();

		// Test positive integers.
		$this->assertTrue( $validator( 1 ) );
		$this->assertTrue( $validator( 100 ) );
		$this->assertTrue( $validator( 999 ) );
		$this->assertTrue( $validator( '50' ) );
		$this->assertTrue( $validator( '100' ) );
	}

	/**
	 * Test that unlimited values are validated correctly.
	 *
	 * @since TBD
	 */
	public function test_validate_unlimited_values() {
		$parameter = new Capacity_Integer( 'capacity' );
		$validator = $parameter->get_validator();

		// Test unlimited values.
		$this->assertTrue( $validator( -1 ) );
		$this->assertTrue( $validator( 'unlimited' ) );
		$this->assertTrue( $validator( '' ) );
	}

	/**
	 * Test that invalid values throw exceptions.
	 *
	 * @since TBD
	 */
	public function test_validate_invalid_values() {
		$parameter = new Capacity_Integer( 'capacity' );
		$validator = $parameter->get_validator();

		// Test invalid values that should throw exceptions.
		$invalid_values = [
			0,
			-2,
			-10,
			'negative',
			'invalid',
			null,
			[],
			(object) [],
		];

		foreach ( $invalid_values as $invalid_value ) {
			$this->expectException( InvalidRestArgumentException::class );
			$validator( $invalid_value );
		}
	}

	/**
	 * Test that the sanitizer converts values correctly.
	 *
	 * @since TBD
	 */
	public function test_sanitizer_converts_values() {
		$parameter = new Capacity_Integer( 'capacity' );
		$sanitizer = $parameter->get_sanitizer();

		// Test unlimited values are converted to -1.
		$this->assertEquals( -1, $sanitizer( 'unlimited' ) );
		$this->assertEquals( -1, $sanitizer( '' ) );
		$this->assertEquals( -1, $sanitizer( -1 ) );

		// Test positive integers are converted to integers.
		$this->assertEquals( 100, $sanitizer( 100 ) );
		$this->assertEquals( 50, $sanitizer( '50' ) );
		$this->assertEquals( 1, $sanitizer( '1' ) );
	}

	/**
	 * Test that the parameter returns the correct default value.
	 *
	 * @since TBD
	 */
	public function test_get_default() {
		$parameter = new Capacity_Integer( 'capacity', null, 50 );
		$this->assertEquals( 50, $parameter->get_default() );

		$parameter_no_default = new Capacity_Integer( 'capacity' );
		$this->assertNull( $parameter_no_default->get_default() );
	}

	/**
	 * Test that the parameter returns the correct example value.
	 *
	 * @since TBD
	 */
	public function test_get_example() {
		$parameter = new Capacity_Integer( 'capacity' );
		$this->assertEquals( 100, $parameter->get_example() );

		$parameter_with_example = new Capacity_Integer( 'capacity' );
		$parameter_with_example->set_example( 200 );
		$this->assertEquals( 200, $parameter_with_example->get_example() );
	}

	/**
	 * Test that the parameter returns the correct array representation.
	 *
	 * @since TBD
	 */
	public function test_to_array() {
		$parameter = new Capacity_Integer( 'capacity', fn() => 'Test capacity' );
		$array = $parameter->to_array();

		$this->assertArrayHasKey( 'description', $array );
		$this->assertArrayHasKey( 'examples', $array );
		$this->assertArrayHasKey( 'type', $array );
		$this->assertEquals( 'integer', $array['type'] );
		$this->assertEquals( 'Test capacity', $array['description'] );
		$this->assertEquals( [ 'limited' => 100, 'unlimited' => -1 ], $array['examples'] );
	}

	/**
	 * Test validation with different string representations of numbers.
	 *
	 * @since TBD
	 */
	public function test_validate_string_numbers() {
		$parameter = new Capacity_Integer( 'capacity' );
		$validator = $parameter->get_validator();

		// Test string representations of positive integers.
		$this->assertTrue( $validator( '1' ) );
		$this->assertTrue( $validator( '100' ) );
		$this->assertTrue( $validator( '999' ) );
	}

	/**
	 * Test that zero is not considered valid.
	 *
	 * @since TBD
	 */
	public function test_zero_is_invalid() {
		$parameter = new Capacity_Integer( 'capacity' );
		$validator = $parameter->get_validator();

		$this->expectException( InvalidRestArgumentException::class );
		$validator( 0 );
	}

	/**
	 * Test that negative numbers other than -1 are invalid.
	 *
	 * @since TBD
	 */
	public function test_negative_numbers_other_than_minus_one_are_invalid() {
		$parameter = new Capacity_Integer( 'capacity' );
		$validator = $parameter->get_validator();

		$invalid_negatives = [ -2, -10, -100, '-2', '-10' ];

		foreach ( $invalid_negatives as $invalid_negative ) {
			$this->expectException( InvalidRestArgumentException::class );
			$validator( $invalid_negative );
		}
	}

	/**
	 * Test that the parameter name is correctly set.
	 *
	 * @since TBD
	 */
	public function test_parameter_name() {
		$parameter = new Capacity_Integer( 'test_capacity' );
		$this->assertEquals( 'test_capacity', $parameter->get_name() );
	}

	/**
	 * Test that the parameter is not required by default.
	 *
	 * @since TBD
	 */
	public function test_parameter_not_required_by_default() {
		$parameter = new Capacity_Integer( 'capacity' );
		$this->assertFalse( $parameter->is_required() );
	}

	/**
	 * Test that the parameter can be set as required.
	 *
	 * @since TBD
	 */
	public function test_parameter_can_be_required() {
		$parameter = new Capacity_Integer( 'capacity', null, null, null, null, true );
		$this->assertTrue( $parameter->is_required() );
	}
}
