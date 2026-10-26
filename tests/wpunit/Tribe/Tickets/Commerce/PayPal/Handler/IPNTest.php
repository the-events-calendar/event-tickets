<?php

namespace Tribe\Tickets\Commerce\PayPal\Handler;

use Faker\Factory;
use Tribe\Tickets\Test\Commerce\PayPal\Transaction_Maker;
use Tribe__Tickets__Commerce__PayPal__Handler__IPN as IPN;
use Tribe__Tickets__Commerce__PayPal__Order as Order;
use Tribe__Tickets__Commerce__PayPal__Stati as Stati;

class IPNTest extends \Codeception\TestCase\WPTestCase {
	use Transaction_Maker;

	/**
	 * @test
	 */
	public function it_should_store_a_pending_notification_with_the_stored_pending_status(): void {
		$faker    = Factory::create();
		$post_id  = static::factory()->post->create();
		$ticket   = tribe( 'tickets.commerce.paypal' )->get_ticket( $post_id, $this->create_paypal_ticket( $post_id, $faker->numberBetween( 10, 50 ) ) );
		$quantity = $faker->numberBetween( 1, 3 );
		$pending  = $this->paypal_transaction( $ticket, $quantity, $this->check_out_with_paypal( $ticket->ID, $quantity ), [ 'payment_status' => 'Pending' ] );

		$this->notify_from_paypal( $pending );

		$attendees = tribe( 'tickets.commerce.paypal' )->get_attendees_by_order_id( $pending['txn_id'] );

		$this->assertSame( array_fill( 0, $quantity, Stati::$pending ), array_column( $attendees, 'order_status' ) );
		$this->assertSame( Stati::$pending, Order::from_order_id( $pending['txn_id'] )->get_status() );
		$this->assertSame( $quantity, tribe( 'tickets.commerce.paypal' )->get_qty_pending( $ticket->ID, true ) );
		$this->assertSame( 0, $this->paypal_sent_emails );
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
