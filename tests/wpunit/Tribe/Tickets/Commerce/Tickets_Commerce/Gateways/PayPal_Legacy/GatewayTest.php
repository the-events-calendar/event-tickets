<?php

namespace Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Legacy;

use TEC\Tickets\Commerce\Gateways\Legacy\Gateway;

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
	 * It should not register the gateway if not shown.
	 *
	 * @test
	 */
	public function should_not_register_gateway_if_not_shown() {
		$sut      = $this->make_instance();
		$commerce = tribe( 'tickets.commerce.paypal' );

		$gateways = [
			'some-gateway' => [
				'label' => 'Some gateway',
				'class' => 'Foo',
			],
		];

		// Tell the logic code it was installed on 5.2+
		tribe_update_option( 'previous_event_tickets_versions', [
			'5.2.1'
		] );
		tribe_update_option( 'latest_event_tickets_version', '5.2.1' );

		$gateways = $sut->register_gateway( $gateways, $commerce );

		// Ensure the gateways sent were returned as expected.
		$this->assertArrayHasKey( 'some-gateway', $gateways );
		$this->assertEquals( 'Some gateway', $gateways['some-gateway']['label'] );
		$this->assertEquals( 'Foo', $gateways['some-gateway']['class'] );

		// The gateway was not registered.
		$this->assertCount( 2, $gateways );
	}

	/**
	 * It should register the gateway if shown.
	 *
	 * @test
	 */
	public function should_register_gateway_if_shown() {
		$sut      = $this->make_instance();
		$commerce = tribe( 'tickets.commerce.paypal' );

		$gateways = [
			'some-gateway' => [
				'label' => 'Some gateway',
				'class' => 'Foo',
			],
		];

		// Tell the logic code it was installed before 5.2+
		tribe_update_option( 'previous_event_tickets_versions', [
			'1.0.0',
		] );
		tribe_update_option( 'latest_event_tickets_version', '1.0.0' );

		// Enable legacy setting.
		tribe_update_option( 'ticket-paypal-enable', '1' );

		$gateways = $sut->register_gateway( $gateways, $commerce );

		// Ensure the gateways sent were returned as expected.
		$this->assertArrayHasKey( 'some-gateway', $gateways );
		$this->assertEquals( 'Some gateway', $gateways['some-gateway']['label'] );
		$this->assertEquals( 'Foo', $gateways['some-gateway']['class'] );

		// The gateway was registered.
		$this->assertArrayHasKey( 'paypal-legacy', $gateways );
		$this->assertEquals( Gateway::class, $gateways['paypal-legacy']['class'] );
	}

	/**
	 * It should not activate gateway if it should not be shown.
	 *
	 * @test
	 */
	public function should_not_activate_gateway_if_it_should_not_be_shown() {
		$sut      = $this->make_instance();
		$commerce = tribe( 'tickets.commerce.paypal' );

		// Tell the logic code it was installed on 5.2+
		tribe_update_option( 'previous_event_tickets_versions', [
			'5.2.1'
		] );
		tribe_update_option( 'latest_event_tickets_version', '5.2.1' );

		$this->assertFalse( $sut->is_active( false, $commerce ) );
	}

	/**
	 * It should not activate gateway if config status is not complete.
	 *
	 * @test
	 */
	public function should_not_activate_gateway_if_config_status_is_not_complete() {
		$sut      = $this->make_instance();
		$commerce = tribe( 'tickets.commerce.paypal' );

		// Tell the logic code it was installed before 5.2+
		tribe_update_option( 'previous_event_tickets_versions', [
			'1.0.0',
		] );
		tribe_update_option( 'latest_event_tickets_version', '1.0.0' );

		// Enable legacy setting.
		tribe_update_option( 'ticket-paypal-enable', '1' );

		// Force legacy email empty so that the config is incomplete.
		tribe_update_option( 'ticket-paypal-email', '' );

		$this->assertFalse( $sut->is_active( false, $commerce ) );
	}

	/**
	 * It should activate gateway if config status is complete.
	 *
	 * @test
	 */
	public function should_activate_gateway_if_config_status_is_complete() {
		$sut      = $this->make_instance();
		$commerce = tribe( 'tickets.commerce.paypal' );

		// Tell the logic code it was installed before 5.2+
		tribe_update_option( 'previous_event_tickets_versions', [
			'1.0.0',
		] );
		tribe_update_option( 'latest_event_tickets_version', '1.0.0' );

		// Enable legacy setting.
		tribe_update_option( 'ticket-paypal-enable', '1' );

		// Update options that mark the config as complete.
		tribe_update_option( 'ticket-paypal-email', 'test@test.local' );
		tribe_update_option( 'ticket-paypal-ipn-enabled', 'yes' );
		tribe_update_option( 'ticket-paypal-ipn-address-set', 'yes' );

		$this->assertTrue( $sut->is_active( false, $commerce ) );
	}

	/**
	 * It should not show gateway if ET version is earlier than 5.2.
	 *
	 * @test
	 */
	public function should_not_show_gateway_if_et_version_is_earlier_than_52() {
		$sut      = $this->make_instance();
		$commerce = tribe( 'tickets.commerce.paypal' );

		// Tell the logic code it was installed on 5.2+
		tribe_update_option( 'previous_event_tickets_versions', [
			'5.2.1'
		] );
		tribe_update_option( 'latest_event_tickets_version', '5.2.1' );

		$this->assertFalse( $sut->should_show( false, $commerce ) );
	}

	/**
	 * It should not show gateway if PayPal not enabled and PayPal email never set.
	 *
	 * @test
	 */
	public function should_not_show_gateway_if_paypal_not_enabled_and_paypal_email_never_set() {
		$sut      = $this->make_instance();
		$commerce = tribe( 'tickets.commerce.paypal' );

		// Tell the logic code it was installed before 5.2+
		tribe_update_option( 'previous_event_tickets_versions', [
			'1.0.0',
		] );
		tribe_update_option( 'latest_event_tickets_version', '1.0.0' );

		// Force legacy settings off/empty.
		tribe_update_option( 'ticket-paypal-enable', '0' );
		tribe_update_option( 'ticket-paypal-email', '' );

		$this->assertFalse( $sut->should_show( false, $commerce ) );
	}

	/**
	 * It should show gateway if PayPal was enabled and PayPal email never set.
	 *
	 * @test
	 */
	public function should_show_gateway_if_paypal_was_enabled_and_paypal_email_never_set() {
		$sut      = $this->make_instance();
		$commerce = tribe( 'tickets.commerce.paypal' );

		// Tell the logic code it was installed before 5.2+
		tribe_update_option( 'previous_event_tickets_versions', [
			'1.0.0',
		] );
		tribe_update_option( 'latest_event_tickets_version', '1.0.0' );

		// Enable legacy setting.
		tribe_update_option( 'ticket-paypal-enable', '1' );

		// Force legacy email empty.
		tribe_update_option( 'ticket-paypal-email', '' );

		$this->assertTrue( $sut->should_show( false, $commerce ) );
	}

	/**
	 * It should show gateway if PayPal not enabled and PayPal email is set.
	 *
	 * @test
	 */
	public function should_show_gateway_if_paypal_not_enabled_and_paypal_email_is_set() {
		$sut      = $this->make_instance();
		$commerce = tribe( 'tickets.commerce.paypal' );

		// Tell the logic code it was installed before 5.2+
		tribe_update_option( 'previous_event_tickets_versions', [
			'1.0.0',
		] );
		tribe_update_option( 'latest_event_tickets_version', '1.0.0' );

		// Disable legacy setting.
		tribe_update_option( 'ticket-paypal-enable', '0' );

		// Set legacy email setting.
		tribe_update_option( 'ticket-paypal-email', 'test@test.local' );

		$this->assertTrue( $sut->should_show( false, $commerce ) );
	}
}