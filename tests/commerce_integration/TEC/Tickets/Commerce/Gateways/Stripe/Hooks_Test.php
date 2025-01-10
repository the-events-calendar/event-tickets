<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Codeception\TestCase\WPTestCase;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use TEC\Tickets\Commerce\Status\Pending;
use TEC\Tickets\Commerce\Status\Created;
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Status_Handler;
use TEC\Tickets\Commerce\Order;

class Hooks_Test extends WPTestCase {
	use Ticket_Maker;
	use With_Uopz;
	use Order_Maker;
	use With_Uopz;

	public function test_it_processes_async_stripe_webhooks() {
		$post = self::factory()->post->create(
			[
				'post_type' => 'page',
			]
		);
		$ticket_id_1 = $this->create_tc_ticket( $post, 10 );
		$ticket_id_2 = $this->create_tc_ticket( $post, 20 );

		$wp_status_slug_from_slug = fn( $slug ) => tribe( Status_Handler::class )->get_by_slug( $slug )->get_wp_slug();

		$order = $this->create_order( [ $ticket_id_1 => 1, $ticket_id_2 => 2 ], [ 'order_status' => Created::SLUG ] );

		$this->assertFalse( as_has_scheduled_action( 'tec_tickets_commerce_async_webhook_process', null, 'tec-tickets-commerce-stripe-webhooks' ) );

		tribe( Order::class )->checkout_completed( $order->ID );

		$this->assertTrue( as_has_scheduled_action( 'tec_tickets_commerce_async_webhook_process', null, 'tec-tickets-commerce-stripe-webhooks' ) );

		tribe( Webhooks::class )->add_pending_webhook( $order->ID, $wp_status_slug_from_slug( Completed::SLUG ), $wp_status_slug_from_slug( Created::SLUG ) );

		$this->assertSame( $wp_status_slug_from_slug( Created::SLUG ), $order->post_status );
		do_action( 'tec_tickets_commerce_async_webhook_process', $order->ID );

		$refreshed_order = tec_tc_get_order( $order->ID );

		$this->assertSame( $wp_status_slug_from_slug( Completed::SLUG ), $refreshed_order->post_status );

		$this->assertEmpty( tribe( Webhooks::class )->get_pending_webhooks( $order->ID ) );

		tribe( Webhooks::class )->add_pending_webhook( $order->ID, $wp_status_slug_from_slug( Completed::SLUG ), $wp_status_slug_from_slug( Created::SLUG ) );

		tribe( Webhooks::class )->add_pending_webhook( $order->ID, $wp_status_slug_from_slug( Pending::SLUG ), $wp_status_slug_from_slug( Completed::SLUG ) );

		do_action( 'tec_tickets_commerce_async_webhook_process', $order->ID );

		$refreshed_order = tec_tc_get_order( $order->ID );

		$this->assertEmpty( tribe( Webhooks::class )->get_pending_webhooks( $order->ID ) );

		$this->assertSame( $wp_status_slug_from_slug( Pending::SLUG ), $refreshed_order->post_status );
	}

	public function test_it_reschedules_async_stripe_webhooks_when_encounter_issues() {
		$post = self::factory()->post->create(
			[
				'post_type' => 'page',
			]
		);
		$ticket_id_1 = $this->create_tc_ticket( $post, 10 );
		$ticket_id_2 = $this->create_tc_ticket( $post, 20 );

		$wp_status_slug_from_slug = fn( $slug ) => tribe( Status_Handler::class )->get_by_slug( $slug )->get_wp_slug();

		$order = $this->create_order( [ $ticket_id_1 => 1, $ticket_id_2 => 2 ], [ 'order_status' => Created::SLUG ] );

		$this->assertFalse( as_has_scheduled_action( 'tec_tickets_commerce_async_webhook_process', null, 'tec-tickets-commerce-stripe-webhooks' ) );
		tribe( Order::class )->checkout_completed( $order->ID );
		$this->assertTrue( as_has_scheduled_action( 'tec_tickets_commerce_async_webhook_process', null, 'tec-tickets-commerce-stripe-webhooks' ) );

		$refreshed_order = tec_tc_get_order( $order->ID );

		$this->assertSame( $wp_status_slug_from_slug( Created::SLUG ), $refreshed_order->post_status );

		tribe( Webhooks::class )->add_pending_webhook( $order->ID, $wp_status_slug_from_slug( Completed::SLUG ), $wp_status_slug_from_slug( Pending::SLUG ) );

		// Issue is encountered here - Different old status
		do_action( 'tec_tickets_commerce_async_webhook_process', $order->ID );
		$this->assertEmpty( tribe( Webhooks::class )->get_pending_webhooks( $order->ID ) );

		$refreshed_order = tec_tc_get_order( $order->ID );

		$this->assertSame( $wp_status_slug_from_slug( Created::SLUG ), $refreshed_order->post_status );

		tribe( Order::class )->lock_order( $order->ID );

		tribe( Webhooks::class )->add_pending_webhook( $order->ID, $wp_status_slug_from_slug( Completed::SLUG ), $wp_status_slug_from_slug( Pending::SLUG ) );

		// Issue is encountered here - Order is locked.
		do_action( 'tec_tickets_commerce_async_webhook_process', $order->ID );
		$this->assertEmpty( tribe( Webhooks::class )->get_pending_webhooks( $order->ID ) );

		$refreshed_order = tec_tc_get_order( $order->ID );

		$this->assertSame( $wp_status_slug_from_slug( Created::SLUG ), $refreshed_order->post_status );

		tribe( Order::class )->unlock_order( $order->ID );

		$this->set_class_fn_return( Order::class, 'modify_status', false );

		tribe( Webhooks::class )->add_pending_webhook( $order->ID, $wp_status_slug_from_slug( Completed::SLUG ), $wp_status_slug_from_slug( Pending::SLUG ) );

		// Issue is encountered here - Modify status will fail.
		do_action( 'tec_tickets_commerce_async_webhook_process', $order->ID );
		$this->assertEmpty( tribe( Webhooks::class )->get_pending_webhooks( $order->ID ) );

		$refreshed_order = tec_tc_get_order( $order->ID );

		$this->assertSame( $wp_status_slug_from_slug( Created::SLUG ), $refreshed_order->post_status );

		uopz_unset_return( Order::class, 'modify_status' );

		tribe( Webhooks::class )->add_pending_webhook( $order->ID, $wp_status_slug_from_slug( Completed::SLUG ), $wp_status_slug_from_slug( Created::SLUG ) );

		// Issue is encountered here - Success
		do_action( 'tec_tickets_commerce_async_webhook_process', $order->ID );

		$refreshed_order = tec_tc_get_order( $order->ID );
		$this->assertEmpty( tribe( Webhooks::class )->get_pending_webhooks( $order->ID ) );

		$this->assertSame( $wp_status_slug_from_slug( Completed::SLUG ), $refreshed_order->post_status );
	}

	public function test_it_should_bail_async_stripe_webhooks_when_end_result_is_done_already() {
		$post = self::factory()->post->create(
			[
				'post_type' => 'page',
			]
		);
		$ticket_id_1 = $this->create_tc_ticket( $post, 10 );
		$ticket_id_2 = $this->create_tc_ticket( $post, 20 );

		$wp_status_slug_from_slug = fn( $slug ) => tribe( Status_Handler::class )->get_by_slug( $slug )->get_wp_slug();

		$order = $this->create_order( [ $ticket_id_1 => 1, $ticket_id_2 => 2 ] );

		$this->assertFalse( as_has_scheduled_action( 'tec_tickets_commerce_async_webhook_process', null, 'tec-tickets-commerce-stripe-webhooks' ) );

		tribe( Order::class )->checkout_completed( $order->ID );

		$this->assertTrue( as_has_scheduled_action( 'tec_tickets_commerce_async_webhook_process', null, 'tec-tickets-commerce-stripe-webhooks' ) );

		tribe( Webhooks::class )->add_pending_webhook( $order->ID, $wp_status_slug_from_slug( Completed::SLUG ), $wp_status_slug_from_slug( Pending::SLUG ) );

		$refreshed_order = tec_tc_get_order( $order->ID );

		$this->assertSame( $wp_status_slug_from_slug( Completed::SLUG ), $refreshed_order->post_status );
		do_action( 'tec_tickets_commerce_async_webhook_process', $order->ID );

		$refreshed_order = tec_tc_get_order( $order->ID );

		$this->assertSame( $wp_status_slug_from_slug( Completed::SLUG ), $refreshed_order->post_status );

		$this->assertEmpty( tribe( Webhooks::class )->get_pending_webhooks( $order->ID ) );
	}
}
