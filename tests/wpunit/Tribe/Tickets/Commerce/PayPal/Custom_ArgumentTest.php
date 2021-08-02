<?php

namespace Tribe\Tickets\Commerce\PayPal;

use Tribe__Tickets__Commerce__PayPal__Custom_Argument as Custom_Argument;

class Custom_ArgumentTest extends \Codeception\TestCase\WPTestCase {

	/**
	 * @return Custom_Argument
	 */
	private function make_instance() {
		return new Custom_Argument();
	}

	/**
	 * @test
	 * it should be instantiatable
	 */
	public function it_should_be_instantiatable() {
		$sut = $this->make_instance();

		$this->assertInstanceOf( Custom_Argument::class, $sut );
	}

	public function valid_args() {
		return [
			[ [] ],
			[ [ 'foo' => 'bar' ] ],
			[ [ 'foo' => 'bar', 'baz' => 23 ] ],
			[ [ 23 => 'bar', 89 => 23 ] ],
			[ [ 'lorem' => 23, 'dolor' => 'bar', 'ipsum' => 'baz', 'sit' => 2389 ] ],
		];
	}

	/**
	 * It should build a key value JSON array
	 *
	 * @test
	 * @dataProvider valid_args
	 */
	public function should_build_a_key_value_json_array( $args ) {
		$this->assertEquals( json_encode( $args ), urldecode_deep( Custom_Argument::encode( $args ) ) );
	}

	/**
	 * It should throw if trying to build args longer than PayPal limit
	 *
	 * @test
	 */
	public function should_throw_if_trying_to_build_args_longer_than_paypal_limit() {
		$limit    = Custom_Argument::$char_limit;
		$too_long = str_repeat( 'a', $limit + 1 );
		$args     = [ 'foo' => $too_long ];

		$this->expectException( \InvalidArgumentException::class );

		Custom_Argument::encode( $args );
	}

	/**
	 * It should allow getting the original custom arguments
	 *
	 * @test
	 */
	public function should_allow_getting_the_original_custom_arguments() {
		$args    = [ 'foo' => 'bar', 'bar' => 'baz', 23 => 89 ];
		$encoded = urlencode_deep( json_encode( $args ) );

		$this->assertEquals( (object) $args, Custom_Argument::decode( $encoded ) );
		$this->assertEquals( $args, Custom_Argument::decode( $encoded, true ) );
	}

	/**
	 * It should return empty array or object when trying to decode invalid JSON
	 *
	 * @test
	 */
	public function should_return_empty_array_or_object_when_trying_to_decode_invalid_json() {
		$this->assertEquals( new \stdClass(), Custom_Argument::decode( 'woot' ) );
		$this->assertEquals( array(), Custom_Argument::decode( 'woot', true ) );
	}
}