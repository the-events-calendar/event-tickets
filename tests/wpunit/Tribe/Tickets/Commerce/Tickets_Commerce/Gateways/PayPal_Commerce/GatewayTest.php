<?php

namespace Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce;

use Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce\SDK\Models\MerchantDetail;
use Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce\SDK_Interface\Repositories\MerchantDetails;

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
		$sut      = $this->make_instance();
		$commerce = tribe( 'tickets.commerce.paypal' );

		/** @var MerchantDetails $merchant_details */
		$merchant_details = tribe( MerchantDetails::class );

		// Delete the option so it never comes up as possibly being connected.
		delete_option( $merchant_details->getAccountKey() );

		$this->assertFalse( $sut->is_active( false, $commerce ) );
	}

	/**
	 * It should activate gateway if config status is complete.
	 *
	 * @test
	 */
	public function should_activate_gateway_if_config_status_is_complete() {
		/** @var MerchantDetails $merchant_details */
		$merchant_details = tribe( MerchantDetails::class );

		// Fill in the merchant ID so it passes the conditional check coming up.
		update_option( $merchant_details->getAccountKey(), [
			'merchantId'             => '12345',
			'merchantIdInPayPal'     => '123456',
			'clientId'               => 'ABCD',
			'clientSecret'           => 'ABCDE',
			'token'                  => [
				'access_token' => 'abcd',
			],
			'accountIsReady'         => '1',
			'supportsCustomPayments' => '1',
			'accountCountry'         => 'US',
		] );

		// Reset the merchant detail object.
		tribe_singleton( MerchantDetail::class, null, [ 'init' ] );

		$sut      = $this->make_instance();
		$commerce = tribe( 'tickets.commerce.paypal' );

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