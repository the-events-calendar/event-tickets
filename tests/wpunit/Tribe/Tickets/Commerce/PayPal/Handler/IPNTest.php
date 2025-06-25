<?php

namespace Tribe\Tickets\Commerce\PayPal\Handler;

use Tribe__Tickets__Commerce__PayPal__Handler__IPN as IPN;

class IPNTest extends \Codeception\TestCase\WPTestCase {

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
		add_filter( "tribe_get_option_ticket-paypal-email", function( $value ) {
			return '';
		}, 10, 1 );

		add_filter( "tribe_get_option_ticket-paypal-ipn-enabled", function( $value ) {
			return '';
		}, 10, 1 );

		add_filter( "tribe_get_option_ticket-paypal-ipn-address-set", function( $value ) {
			return '';
		}, 10, 1 );

		$ipn = $this->make_instance();

		$this->assertEquals( 'incomplete', $ipn->get_config_status() );
		$this->assertEquals( 'incomplete', $ipn->get_config_status( 'slug' ) );
		$this->assertIsString( $ipn->get_config_status( 'label' ) );
		$this->assertEquals( 'complete', $ipn->get_config_status( 'slug', 'complete' ) );
		$this->assertIsString( $ipn->get_config_status( 'label', 'complete' ) );
		$this->assertEquals( 'incomplete', $ipn->get_config_status( 'slug', 'incomplete' ) );
		$this->assertIsString( $ipn->get_config_status( 'label', 'incomplete' ) );
		$this->assertFalse( $ipn->get_config_status( 'slug', 'foo' ) );
		$this->assertFalse( $ipn->get_config_status( 'label', 'foo' ) );

		// Clean up filter
		remove_all_filters( 'tribe_get_option' );
	}

	/**
	 * Test get_config_status with complete option
	 */
	public function test_get_config_status_with_complete_option() {
		add_filter( "tribe_get_option_ticket-paypal-email", function( $value ) {
			return 'foo@bar.baz';
		}, 10, 1 );

		add_filter( "tribe_get_option_ticket-paypal-ipn-enabled", function( $value ) {
			return 'yes';
		}, 10, 1 );

		add_filter( "tribe_get_option_ticket-paypal-ipn-address-set", function( $value ) {
			return 'yes';
		}, 10, 1 );

		$ipn = $this->make_instance();

		$this->assertEquals( 'complete', $ipn->get_config_status() );
		$this->assertEquals( 'complete', $ipn->get_config_status( 'slug' ) );
		$this->assertIsString( $ipn->get_config_status( 'label' ) );
		$this->assertEquals( 'complete', $ipn->get_config_status( 'slug', 'complete' ) );
		$this->assertIsString( $ipn->get_config_status( 'label', 'complete' ) );
		$this->assertEquals( 'incomplete', $ipn->get_config_status( 'slug', 'incomplete' ) );
		$this->assertIsString( $ipn->get_config_status( 'label', 'incomplete' ) );
		$this->assertFalse( $ipn->get_config_status( 'slug', 'foo' ) );
		$this->assertFalse( $ipn->get_config_status( 'label', 'foo' ) );

		// Clean up filter
		remove_all_filters( 'tribe_get_option' );
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
		// Mock tribe_get_option to return the incomplete configuration values from data provider
		add_filter( 'tribe_get_option', function( $value, $option_name, $default ) use ( $map ) {
			if ( isset( $map[ $option_name ] ) ) {
				return $map[ $option_name ];
			}

			return $value;
		}, 10, 3 );

		$ipn = $this->make_instance();

		$this->assertEquals( 'incomplete', $ipn->get_config_status() );
		$this->assertEquals( 'incomplete', $ipn->get_config_status( 'slug' ) );
		$this->assertIsString( $ipn->get_config_status( 'label' ) );
		$this->assertEquals( 'complete', $ipn->get_config_status( 'slug', 'complete' ) );
		$this->assertIsString( $ipn->get_config_status( 'label', 'complete' ) );
		$this->assertEquals( 'incomplete', $ipn->get_config_status( 'slug', 'incomplete' ) );
		$this->assertIsString( $ipn->get_config_status( 'label', 'incomplete' ) );
		$this->assertFalse( $ipn->get_config_status( 'slug', 'foo' ) );
		$this->assertFalse( $ipn->get_config_status( 'label', 'foo' ) );

		// Clean up filter
		remove_all_filters( 'tribe_get_option' );
	}
}
