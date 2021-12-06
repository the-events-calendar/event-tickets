<?php

namespace Tribe\Tickets\Commerce\PayPal\Handler;

use Tribe__Tickets__Commerce__PayPal__Handler__IPN as IPN;


class IPNTest extends \Codeception\TestCase\WPTestCase {

	/**
	 * @return IPN
	 */
	private function make_instance() {
		return new IPN();
	}

	/**
	 * @test
	 * it should be instantiatable
	 */
	public function it_should_be_instantiatable() {
		$sut = $this->make_instance();

		$this->assertInstanceOf( IPN::class, $sut );
	}

	/**
	 * Test get_config_status with empty option
	 */
	public function test_get_config_status_with_empty_option() {
		$ipn = $this->make_instance();
		tribe_update_option( 'ticket-paypal-email', null );
		tribe_update_option( 'ticket-paypal-ipn-enabled', null );
		tribe_update_option( 'ticket-paypal-ipn-address-set', null );

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

		$ipn = $this->make_instance();

		tribe_update_option( 'ticket-paypal-email', 'foo@bar.baz' );
		tribe_update_option( 'ticket-paypal-ipn-enabled', 'yes' );
		tribe_update_option( 'ticket-paypal-ipn-address-set', 'yes' );

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
		tribe_update_option( 'ticket-paypal-email', $map[ 'ticket-paypal-email' ] );
		tribe_update_option( 'ticket-paypal-ipn-enabled', $map[ 'ticket-paypal-ipn-enabled' ] );
		tribe_update_option( 'ticket-paypal-ipn-address-set', $map[ 'ticket-paypal-ipn-address-set' ] );

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