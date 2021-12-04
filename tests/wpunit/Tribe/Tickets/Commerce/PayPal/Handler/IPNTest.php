<?php

namespace Tribe\Tickets\Commerce\PayPal\Handler;

use Tribe__Tickets__Commerce__PayPal__Handler__IPN as IPN;
use function tad\FunctionMockerLe\define as define_function;

class IPNTest extends \Codeception\Test\Unit {

	/**
	 * @test
	 * it should be instantiatable
	 */
	public function it_should_be_instantiatable() {
		$sut = $this->make_instance();

		$this->assertInstanceOf( IPN::class, $sut );
	}

	/**
	 * @return IPN
	 */
	private function make_instance() {
		return new IPN();
	}

	/**
	 * Test get_config_status with empty option
	 */
	public function test_get_config_status_with_empty_option() {
		$this->markTestSkipped( 'This came from UNIT tests we need to remove define_function usage and make sure it works.' );
//		define_function( 'tribe_get_option', function () {
//			return '';
//		} );

		$ipn = $this->make_instance();

		$this->assertEquals( 'incomplete', $ipn->get_config_status() );
		$this->assertEquals( 'incomplete', $ipn->get_config_status( 'slug' ) );
		$this->assertInternalType( 'string', $ipn->get_config_status( 'label' ) );
		$this->assertEquals( 'complete', $ipn->get_config_status( 'slug', 'complete' ) );
		$this->assertInternalType( 'string', $ipn->get_config_status( 'label', 'complete' ) );
		$this->assertEquals( 'incomplete', $ipn->get_config_status( 'slug', 'incomplete' ) );
		$this->assertInternalType( 'string', $ipn->get_config_status( 'label', 'incomplete' ) );
		$this->assertFalse( $ipn->get_config_status( 'slug', 'foo' ) );
		$this->assertFalse( $ipn->get_config_status( 'label', 'foo' ) );

	}

	/**
	 * Test get_config_status with complete option
	 */
	public function test_get_config_status_with_complete_option() {
		$this->markTestSkipped( 'This came from UNIT tests we need to remove define_function usage and make sure it works.' );
//		define_function( 'tribe_get_option', function ( $name ) {
//			$map = [
//				'ticket-paypal-email'           => 'foo@bar.baz',
//				'ticket-paypal-ipn-enabled'     => 'yes',
//				'ticket-paypal-ipn-address-set' => 'yes',
//			];
//			if ( ! isset( $map[ $name ] ) ) {
//				throw new \RuntimeException( "No call was expected for option {$name}" );
//			}
//
//			return $map[ $name ];
//		} );

		$ipn = $this->make_instance();

		$this->assertEquals( 'complete', $ipn->get_config_status() );
		$this->assertEquals( 'complete', $ipn->get_config_status( 'slug' ) );
		$this->assertInternalType( 'string', $ipn->get_config_status( 'label' ) );
		$this->assertEquals( 'complete', $ipn->get_config_status( 'slug', 'complete' ) );
		$this->assertInternalType( 'string', $ipn->get_config_status( 'label', 'complete' ) );
		$this->assertEquals( 'incomplete', $ipn->get_config_status( 'slug', 'incomplete' ) );
		$this->assertInternalType( 'string', $ipn->get_config_status( 'label', 'incomplete' ) );
		$this->assertFalse( $ipn->get_config_status( 'slug', 'foo' ) );
		$this->assertFalse( $ipn->get_config_status( 'label', 'foo' ) );
	}

	public function incomplete_option_map() {
		return [
			[
				// no email
				[
					'ticket-paypal-email'           => '',
					'ticket-paypal-ipn-enabled'     => 'yes',
					'ticket-paypal-ipn-address-set' => 'yes',
				],
			],
			[
				// IPN not enabled
				[
					'ticket-paypal-email'           => 'foo@bar.baz',
					'ticket-paypal-ipn-enabled'     => 'no',
					'ticket-paypal-ipn-address-set' => 'yes',
				],
			],
			[
				// IPN address not set
				[
					'ticket-paypal-email'           => 'foo@bar.baz',
					'ticket-paypal-ipn-enabled'     => 'yes',
					'ticket-paypal-ipn-address-set' => 'no',
				],
			],
			[
				// IPN not enabled, empty
				[
					'ticket-paypal-email'           => 'foo@bar.baz',
					'ticket-paypal-ipn-enabled'     => '',
					'ticket-paypal-ipn-address-set' => 'yes',
				],
			],
			[
				// IPN address not set, empty
				[
					'ticket-paypal-email'           => 'foo@bar.baz',
					'ticket-paypal-ipn-enabled'     => 'yes',
					'ticket-paypal-ipn-address-set' => '',
				],
			],
		];
	}

	/**
	 * Test get_config_status with incomplete option
	 *
	 * @dataProvider incomplete_option_map
	 */
	public function test_get_config_status_with_incomplete_option( $map ) {
		$this->markTestSkipped( 'This came from UNIT tests we need to remove define_function usage and make sure it works.' );

//		define_function( 'tribe_get_option', function ( $name ) use ( $map ) {
//			if ( ! isset( $map[ $name ] ) ) {
//				throw new \RuntimeException( "No call was expected for option {$name}" );
//			}
//
//			return $map[ $name ];
//		} );

		$ipn = $this->make_instance();

		$this->assertEquals( 'incomplete', $ipn->get_config_status() );
		$this->assertEquals( 'incomplete', $ipn->get_config_status( 'slug' ) );
		$this->assertInternalType( 'string', $ipn->get_config_status( 'label' ) );
		$this->assertEquals( 'complete', $ipn->get_config_status( 'slug', 'complete' ) );
		$this->assertInternalType( 'string', $ipn->get_config_status( 'label', 'complete' ) );
		$this->assertEquals( 'incomplete', $ipn->get_config_status( 'slug', 'incomplete' ) );
		$this->assertInternalType( 'string', $ipn->get_config_status( 'label', 'incomplete' ) );
		$this->assertFalse( $ipn->get_config_status( 'slug', 'foo' ) );
		$this->assertFalse( $ipn->get_config_status( 'label', 'foo' ) );
	}
}