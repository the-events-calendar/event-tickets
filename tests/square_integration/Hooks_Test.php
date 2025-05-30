<?php

namespace TEC\Tickets\Commerce\Gateways\Square;

use TEC\Common\Tests\Provider\Controller_Test_Case;
use Tribe\Tests\Traits\With_Clock_Mock;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe__Date_Utils as Dates;
use TEC\Tickets\Commerce\Status\Pending;
use TEC\Tickets\Commerce\Status\Created;
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Status_Handler;
use TEC\Tickets\Commerce\Order as Commerce_Order;
use Tribe\Tests\Traits\With_Uopz;

class Hooks_Test extends Controller_Test_Case {
	use With_Uopz;
	use Order_Maker;
	use Ticket_Maker;
	use With_Clock_Mock;

	protected string $controller_class = Hooks::class;

	/**
	 * @test
	 */
	public function it_should_add_the_gateway_to_the_gateways_array(): void {
		$this->make_controller()->register();

		$gateways = apply_filters( 'tec_tickets_commerce_gateways', [] );
		$this->assertArrayHasKey( Gateway::get_key(), $gateways );
	}

	/**
	 * @test
	 */
	public function it_should_filter_the_orders_repository_schema(): void {
		$this->make_controller()->register();

		$schema = apply_filters( 'tec_repository_schema_tc_orders', [], null );
		$this->assertArrayHasKey( 'square_payment_id', $schema );
		$this->assertArrayHasKey( 'square_payment_id_not', $schema );
		$this->assertArrayHasKey( 'square_refund_id', $schema );
		$this->assertArrayHasKey( 'square_refund_id_not', $schema );
	}

	/**
	 * @test
	 */
	public function it_should_filter_the_order_get_value_refunded(): void {
		$this->make_controller()->register();

		$refunds = [
			[
				'data' => [
					'object' => [
						'refund' => [
							'id' => 'refund_123',
							'amount_money' => [
								'amount' => 1000,
							],
						],
					],
				],
			],
			[
				'data' => [
					'object' => [
						'refund' => [
							'id' => 'refund_123',
							'amount_money' => [
								'amount' => 500,
							],
						],
					],
				],
			],
			[
				'data' => [
					'object' => [
						'refund' => [
							'id' => 'refund_456',
							'amount_money' => [
								'amount' => 1500,
							],
						],
					],
				],
			],
			[
				'data' => [
					'object' => [
						'refund' => [
							'id' => 'refund_456',
							'amount_money' => [
								'amount' => 2000,
							],
						],
					],
				],
			],
		];
		$refunded = apply_filters( 'tec_tickets_commerce_order_square_get_value_refunded', null, $refunds );
		$this->assertEquals( 2500, $refunded );
	}

	/**
	 * @test
	 */
	public function it_processes_async_webhooks() {
		$this->freeze_time( Dates::immutable( '2024-06-13 17:25:00' ) );
		$this->make_controller()->register();
		$post = self::factory()->post->create();
		$ticket_id_1 = $this->create_tc_ticket( $post, 10 );
		$ticket_id_2 = $this->create_tc_ticket( $post, 20 );

		$wp_status_slug_from_slug = fn( $slug ) => tribe( Status_Handler::class )->get_by_slug( $slug )->get_wp_slug();

		$order = $this->create_order_through_square( [ $ticket_id_1 => 1, $ticket_id_2 => 2 ], [ 'order_status' => Created::SLUG ] );

		$this->assertFalse( as_has_scheduled_action( 'tec_tickets_commerce_async_webhook_process', null, 'tec-tickets-commerce-webhooks' ) );

		tribe( Commerce_Order::class )->set_on_checkout_screen_hold( $order->ID );

		$this->assertTrue( as_has_scheduled_action( 'tec_tickets_commerce_async_webhook_process', null, 'tec-tickets-commerce-webhooks' ) );

		tribe( Webhooks::class )->add_pending_webhook( $order->ID, $wp_status_slug_from_slug( Completed::SLUG ), $wp_status_slug_from_slug( Created::SLUG ) );

		$this->assertSame( $wp_status_slug_from_slug( Created::SLUG ), $order->post_status );
		$this->freeze_time( Dates::immutable( '2024-06-13 17:51:00' ) );
		do_action( 'tec_tickets_commerce_async_webhook_process', $order->ID, 0 );

		$refreshed_order = tec_tc_get_order( $order->ID );

		$this->assertSame( $wp_status_slug_from_slug( Completed::SLUG ), $refreshed_order->post_status );

		$this->assertEmpty( tribe( Webhooks::class )->get_pending_webhooks( $order->ID ) );

		tribe( Webhooks::class )->add_pending_webhook( $order->ID, $wp_status_slug_from_slug( Completed::SLUG ), $wp_status_slug_from_slug( Created::SLUG ) );

		tribe( Webhooks::class )->add_pending_webhook( $order->ID, $wp_status_slug_from_slug( Pending::SLUG ), $wp_status_slug_from_slug( Completed::SLUG ) );

		do_action( 'tec_tickets_commerce_async_webhook_process', $order->ID, 1 );

		$refreshed_order = tec_tc_get_order( $order->ID );

		$this->assertEmpty( tribe( Webhooks::class )->get_pending_webhooks( $order->ID ) );

		$this->assertSame( $wp_status_slug_from_slug( Pending::SLUG ), $refreshed_order->post_status );
	}

	/**
	 * @test
	 */
	public function it_reschedules_async_webhooks_when_encounter_issues() {
		$this->freeze_time( Dates::immutable( '2024-06-13 17:25:00' ) );
		$this->make_controller()->register();
		$post = self::factory()->post->create();
		$ticket_id_1 = $this->create_tc_ticket( $post, 10 );
		$ticket_id_2 = $this->create_tc_ticket( $post, 20 );

		$wp_status_slug_from_slug = fn( $slug ) => tribe( Status_Handler::class )->get_by_slug( $slug )->get_wp_slug();

		$order = $this->create_order_through_square( [ $ticket_id_1 => 1, $ticket_id_2 => 2 ], [ 'order_status' => Created::SLUG ] );

		$this->assertFalse( as_has_scheduled_action( 'tec_tickets_commerce_async_webhook_process', null, 'tec-tickets-commerce-webhooks' ) );
		tribe( Commerce_Order::class )->set_on_checkout_screen_hold( $order->ID );
		$this->freeze_time( Dates::immutable( '2024-06-13 17:51:00' ) );
		$this->assertTrue( as_has_scheduled_action( 'tec_tickets_commerce_async_webhook_process', null, 'tec-tickets-commerce-webhooks' ) );

		$refreshed_order = tec_tc_get_order( $order->ID );

		$this->assertSame( $wp_status_slug_from_slug( Created::SLUG ), $refreshed_order->post_status );

		tribe( Webhooks::class )->add_pending_webhook( $order->ID, $wp_status_slug_from_slug( Completed::SLUG ), $wp_status_slug_from_slug( Pending::SLUG ) );

		// Issue is encountered here - Different old status
		do_action( 'tec_tickets_commerce_async_webhook_process', $order->ID, 0 );
		$this->assertEmpty( tribe( Webhooks::class )->get_pending_webhooks( $order->ID ) );

		$refreshed_order = tec_tc_get_order( $order->ID );

		$this->assertSame( $wp_status_slug_from_slug( Created::SLUG ), $refreshed_order->post_status );

		tribe( Commerce_Order::class )->lock_order( $order->ID );

		tribe( Webhooks::class )->add_pending_webhook( $order->ID, $wp_status_slug_from_slug( Completed::SLUG ), $wp_status_slug_from_slug( Pending::SLUG ) );

		// Issue is encountered here - Order is locked.
		do_action( 'tec_tickets_commerce_async_webhook_process', $order->ID, 0 );
		$this->assertEmpty( tribe( Webhooks::class )->get_pending_webhooks( $order->ID ) );

		$refreshed_order = tec_tc_get_order( $order->ID );

		$this->assertSame( $wp_status_slug_from_slug( Created::SLUG ), $refreshed_order->post_status );

		tribe( Commerce_Order::class )->unlock_order( $order->ID );

		$this->set_class_fn_return( Commerce_Order::class, 'modify_status', false );

		tribe( Webhooks::class )->add_pending_webhook( $order->ID, $wp_status_slug_from_slug( Completed::SLUG ), $wp_status_slug_from_slug( Pending::SLUG ) );

		// Issue is encountered here - Modify status will fail.
		do_action( 'tec_tickets_commerce_async_webhook_process', $order->ID, 0 );
		$this->assertEmpty( tribe( Webhooks::class )->get_pending_webhooks( $order->ID ) );

		$refreshed_order = tec_tc_get_order( $order->ID );

		$this->assertSame( $wp_status_slug_from_slug( Created::SLUG ), $refreshed_order->post_status );

		uopz_unset_return( Commerce_Order::class, 'modify_status' );

		tribe( Webhooks::class )->add_pending_webhook( $order->ID, $wp_status_slug_from_slug( Completed::SLUG ), $wp_status_slug_from_slug( Created::SLUG ) );

		// Success
		do_action( 'tec_tickets_commerce_async_webhook_process', $order->ID, 0 );

		$refreshed_order = tec_tc_get_order( $order->ID );
		$this->assertEmpty( tribe( Webhooks::class )->get_pending_webhooks( $order->ID ) );

		$this->assertSame( $wp_status_slug_from_slug( Completed::SLUG ), $refreshed_order->post_status );
	}

	/**
	 * @test
	 */
	public function it_should_bail_async_webhooks_when_end_result_is_done_already() {
		$this->freeze_time( Dates::immutable( '2024-06-13 17:25:00' ) );
		$this->make_controller()->register();
		$post = self::factory()->post->create();
		$ticket_id_1 = $this->create_tc_ticket( $post, 10 );
		$ticket_id_2 = $this->create_tc_ticket( $post, 20 );

		$wp_status_slug_from_slug = fn( $slug ) => tribe( Status_Handler::class )->get_by_slug( $slug )->get_wp_slug();

		$order = $this->create_order_through_square( [ $ticket_id_1 => 1, $ticket_id_2 => 2 ] );

		$this->assertFalse( as_has_scheduled_action( 'tec_tickets_commerce_async_webhook_process', null, 'tec-tickets-commerce-webhooks' ) );

		tribe( Commerce_Order::class )->set_on_checkout_screen_hold( $order->ID );

		$this->assertTrue( as_has_scheduled_action( 'tec_tickets_commerce_async_webhook_process', null, 'tec-tickets-commerce-webhooks' ) );

		tribe( Webhooks::class )->add_pending_webhook( $order->ID, $wp_status_slug_from_slug( Completed::SLUG ), $wp_status_slug_from_slug( Pending::SLUG ) );

		$refreshed_order = tec_tc_get_order( $order->ID );

		$this->assertSame( $wp_status_slug_from_slug( Completed::SLUG ), $refreshed_order->post_status );
		$this->freeze_time( Dates::immutable( '2024-06-13 17:51:00' ) );
		do_action( 'tec_tickets_commerce_async_webhook_process', $order->ID, 0 );

		$refreshed_order = tec_tc_get_order( $order->ID );

		$this->assertSame( $wp_status_slug_from_slug( Completed::SLUG ), $refreshed_order->post_status );

		$this->assertEmpty( tribe( Webhooks::class )->get_pending_webhooks( $order->ID ) );
	}

	/**
	 * @test
	 */
	public function it_reschedules_async_webhooks_when_triggered_too_early() {
		$this->freeze_time( Dates::immutable( '2024-06-13 17:25:00' ) );
		$this->make_controller()->register();
		$post = self::factory()->post->create();
		$ticket_id_1 = $this->create_tc_ticket( $post, 10 );
		$ticket_id_2 = $this->create_tc_ticket( $post, 20 );

		$wp_status_slug_from_slug = fn( $slug ) => tribe( Status_Handler::class )->get_by_slug( $slug )->get_wp_slug();

		$order = $this->create_order_through_square( [ $ticket_id_1 => 1, $ticket_id_2 => 2 ], [ 'order_status' => Created::SLUG ] );

		$this->assertFalse( as_has_scheduled_action( 'tec_tickets_commerce_async_webhook_process', null, 'tec-tickets-commerce-webhooks' ) );
		tribe( Commerce_Order::class )->set_on_checkout_screen_hold( $order->ID );

		$this->assertTrue( as_has_scheduled_action( 'tec_tickets_commerce_async_webhook_process', null, 'tec-tickets-commerce-webhooks' ) );

		$refreshed_order = tec_tc_get_order( $order->ID, OBJECT, 'raw', true );

		$this->assertTrue( $refreshed_order->on_checkout_hold - time() > 0 );

		$this->assertSame( $wp_status_slug_from_slug( Created::SLUG ), $refreshed_order->post_status );

		tribe( Webhooks::class )->add_pending_webhook( $order->ID, $wp_status_slug_from_slug( Completed::SLUG ), $wp_status_slug_from_slug( Created::SLUG ) );

		// Time is still frozen so webhook process should be rescheduled.
		do_action( 'tec_tickets_commerce_async_webhook_process', $order->ID, 0 );
		$this->assertNotEmpty( tribe( Webhooks::class )->get_pending_webhooks( $order->ID ) );

		$refreshed_order = tec_tc_get_order( $order->ID );

		$this->assertSame( $wp_status_slug_from_slug( Created::SLUG ), $refreshed_order->post_status );

		$this->freeze_time( Dates::immutable( '2024-06-13 17:51:00' ) );

		do_action( 'tec_tickets_commerce_async_webhook_process', $order->ID, 0 );

		$refreshed_order = tec_tc_get_order( $order->ID );
		$this->assertEmpty( tribe( Webhooks::class )->get_pending_webhooks( $order->ID ) );

		$this->assertSame( $wp_status_slug_from_slug( Completed::SLUG ), $refreshed_order->post_status );
	}
}
