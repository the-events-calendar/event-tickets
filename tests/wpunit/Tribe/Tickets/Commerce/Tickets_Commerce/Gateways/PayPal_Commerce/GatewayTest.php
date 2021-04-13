<?php

namespace Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce;

class GatewayTest extends \Codeception\TestCase\WPTestCase {

	/**
	 * @return Gateway
	 */
	private function make_instance() {
		return new Gateway();
	}

	/**
	 * @test
	 * it should be instantiatable
	 */
	public function it_should_be_instantiatable() {
		$sut = $this->make_instance();

		$this->assertInstanceOf( Gateway::class, $sut );
	}

	/**
	 * It should register the gateway.
	 *
	 * @test
	 */
	public function should_register_gateway() {
		$sut      = $this->make_instance();
		$commerce = tribe( 'tickets.commerce.paypal' );

		$gateways = [
			'some-gateway' => [
				'label' => 'Some gateway',
				'class' => 'Foo',
			],
		];

		$gateways = $sut->register_gateway( $gateways, $commerce );

		// Ensure the gateways sent were returned as expected.
		$this->assertArrayHasKey( 'some-gateway', $gateways );
		$this->assertEquals( 'Some gateway', $gateways['some-gateway']['label'] );
		$this->assertEquals( 'Foo', $gateways['some-gateway']['class'] );

		// The gateway was registered.
		$this->assertArrayHasKey( 'paypal-commerce', $gateways );
		$this->assertEquals( Gateway::class, $gateways['paypal-commerce']['class'] );
	}

	/**
	 * It should not activate gateway if config status is not complete.
	 *
	 * @test
	 */
	public function should_not_activate_gateway_if_config_status_is_not_complete() {
		$this->markTestIncomplete();

		$sut      = $this->make_instance();
		$commerce = tribe( 'tickets.commerce.paypal' );

		// @todo Update this when the is_active logic is finished.

		$this->assertFalse( $sut->is_active( false, $commerce ) );
	}

	/**
	 * It should activate gateway if config status is complete.
	 *
	 * @test
	 */
	public function should_activate_gateway_if_config_status_is_complete() {
		$this->markTestIncomplete();

		$sut      = $this->make_instance();
		$commerce = tribe( 'tickets.commerce.paypal' );

		// @todo Update this when the is_active logic is finished.

		$this->assertTrue( $sut->is_active( false, $commerce ) );
	}

	/**
	 * It should show gateway.
	 *
	 * @test
	 */
	public function should_show_gateway() {
		$sut      = $this->make_instance();
		$commerce = tribe( 'tickets.commerce.paypal' );

		$this->assertTrue( $sut->should_show( false, $commerce ) );
	}
}