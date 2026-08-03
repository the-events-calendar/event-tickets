<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Partially_Refunded;
use TEC\Tickets\Commerce\Status\Refunded;
use TEC\Tickets\Commerce\Gateways\Stripe\Webhooks\Charge_Webhook;

class Partial_Refund_Test extends \Codeception\TestCase\WPTestCase {

	use Ticket_Maker;
	use Order_Maker;

	/**
	 * @test
	 */
	public function it_should_detect_partial_and_full_stripe_charges() {
		$this->assertFalse(
			Charge_Webhook::is_charge_fully_refunded(
				[
					'refunded'        => false,
					'amount_refunded' => 500,
					'amount_captured' => 2000,
				]
			)
		);

		$this->assertTrue(
			Charge_Webhook::is_charge_fully_refunded(
				[
					'refunded'        => true,
					'amount_refunded' => 2000,
					'amount_captured' => 2000,
				]
			)
		);

		$this->assertTrue(
			Charge_Webhook::is_charge_fully_refunded(
				[
					'refunded'        => false,
					'amount_refunded' => 2000,
					'amount_captured' => 2000,
				]
			)
		);
	}

	/**
	 * @test
	 */
	public function it_should_resolve_refund_status_from_amount() {
		$post_id   = self::factory()->post->create( [ 'post_type' => 'page' ] );
		$ticket_id = $this->create_tc_ticket( $post_id, 25 );
		$order     = $this->create_order( [ $ticket_id => 1 ] );

		$partial = tribe( Order::class )->resolve_refund_status( 1000, $order );
		$this->assertInstanceOf( Partially_Refunded::class, $partial );

		$full = tribe( Order::class )->resolve_refund_status( 2500, $order );
		$this->assertInstanceOf( Refunded::class, $full );
	}

	/**
	 * @test
	 */
	public function it_should_keep_attendees_on_partial_refund_and_archive_on_full_refund() {
		$post_id   = self::factory()->post->create( [ 'post_type' => 'page' ] );
		$ticket_id = $this->create_tc_ticket( $post_id, 20 );
		$order     = $this->create_order( [ $ticket_id => 1 ] );

		$this->assertEquals( tribe( Completed::class )->get_wp_slug(), $order->post_status );

		$attendees = tec_tc_attendees()->by( 'parent', $order->ID )->by( 'status', 'any' )->all();
		$this->assertNotEmpty( $attendees );
		$attendee_id = $attendees[0]->ID;
		$this->assertEquals( 'publish', get_post_status( $attendee_id ) );

		$partial_payload = [
			'id'              => 'ch_partial',
			'refunded'        => false,
			'amount_refunded' => 500,
			'amount_captured' => 2000,
		];

		$this->assertTrue(
			tribe( Order::class )->modify_status(
				$order->ID,
				Partially_Refunded::SLUG,
				[ 'gateway_payload' => $partial_payload ]
			)
		);

		$order = tec_tc_get_order( $order->ID );
		$this->assertEquals( tribe( Partially_Refunded::class )->get_wp_slug(), $order->post_status );
		$this->assertEquals( 'publish', get_post_status( $attendee_id ) );

		$full_payload = [
			'id'              => 'ch_full',
			'refunded'        => true,
			'amount_refunded' => 2000,
			'amount_captured' => 2000,
		];

		$this->assertTrue(
			tribe( Order::class )->modify_status(
				$order->ID,
				Refunded::SLUG,
				[ 'gateway_payload' => $full_payload ]
			)
		);

		$order = tec_tc_get_order( $order->ID );
		$this->assertEquals( tribe( Refunded::class )->get_wp_slug(), $order->post_status );
		$this->assertEquals( 'trash', get_post_status( $attendee_id ) );
	}

	/**
	 * @test
	 */
	public function it_should_allow_stacked_partial_refunds() {
		$post_id   = self::factory()->post->create( [ 'post_type' => 'page' ] );
		$ticket_id = $this->create_tc_ticket( $post_id, 30 );
		$order     = $this->create_order( [ $ticket_id => 1 ] );

		$this->assertTrue(
			tribe( Order::class )->modify_status(
				$order->ID,
				Partially_Refunded::SLUG,
				[
					'gateway_payload' => [
						'amount_refunded' => 500,
						'amount_captured' => 3000,
					],
				]
			)
		);

		$this->assertTrue(
			tribe( Order::class )->modify_status(
				$order->ID,
				Partially_Refunded::SLUG,
				[
					'gateway_payload' => [
						'amount_refunded' => 1000,
						'amount_captured' => 3000,
					],
				]
			)
		);

		$order = tec_tc_get_order( $order->ID );
		$this->assertEquals( tribe( Partially_Refunded::class )->get_wp_slug(), $order->post_status );
		$this->assertCount( 2, $order->gateway_payload[ Partially_Refunded::SLUG ] ?? [] );
	}
}
