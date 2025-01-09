<?php

namespace TEC\Tickets\Commerce;

use TEC\Tickets\Commerce\Cart;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Codeception\TestCase\WPTestCase;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use TEC\Tickets\Commerce\Status\Pending;
use TEC\Tickets\Commerce\Status\Created;
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Status_Handler;
use Exception;

class Provider_Test extends WPTestCase {
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

		tribe( Order::class )->checkout_completed( $order->ID );

		$this->assertSame( $wp_status_slug_from_slug( Created::SLUG ), $order->post_status );
		do_action( 'tec_tickets_commerce_async_webhook_process', $order->ID, $wp_status_slug_from_slug( Completed::SLUG ), [], $wp_status_slug_from_slug( Created::SLUG ), 1 );

		$refreshed_order = tec_tc_get_order( $order->ID );

		$this->assertSame( $wp_status_slug_from_slug( Completed::SLUG ), $refreshed_order->post_status );
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
		$has_scheduled_action = fn() => as_has_scheduled_action( 'tec_tickets_commerce_async_webhook_process', null, 'tec-tickets-commerce-stripe-webhooks' );

		$order = $this->create_order( [ $ticket_id_1 => 1, $ticket_id_2 => 2 ], [ 'order_status' => Created::SLUG ] );

		$this->assertSame( $wp_status_slug_from_slug( Created::SLUG ), $order->post_status );
		$this->assertFalse( $has_scheduled_action() );

		// Issue is encountered here - Different old status
		do_action( 'tec_tickets_commerce_async_webhook_process', $order->ID, $wp_status_slug_from_slug( Completed::SLUG ), [], $wp_status_slug_from_slug( Pending::SLUG ), 1 );

		$refreshed_order = tec_tc_get_order( $order->ID );

		$this->assertSame( $wp_status_slug_from_slug( Created::SLUG ), $refreshed_order->post_status );
		$this->assertTrue( $has_scheduled_action() );

		as_unschedule_all_actions( 'tec_tickets_commerce_async_webhook_process' );

		$this->assertFalse( $has_scheduled_action() );

		// Issue is encountered here - Order needs to have its checkout completed.
		do_action( 'tec_tickets_commerce_async_webhook_process', $order->ID, $wp_status_slug_from_slug( Completed::SLUG ), [], $wp_status_slug_from_slug( Created::SLUG ), 1 );

		$refreshed_order = tec_tc_get_order( $order->ID );

		$this->assertSame( $wp_status_slug_from_slug( Created::SLUG ), $refreshed_order->post_status );
		$this->assertTrue( $has_scheduled_action() );

		as_unschedule_all_actions( 'tec_tickets_commerce_async_webhook_process' );

		$this->assertFalse( $has_scheduled_action() );

		tribe( Order::class )->checkout_completed( $order->ID );
		tribe( Order::class )->lock_order( $order->ID );

		// Issue is encountered here - Order is locked.
		do_action( 'tec_tickets_commerce_async_webhook_process', $order->ID, $wp_status_slug_from_slug( Completed::SLUG ), [], $wp_status_slug_from_slug( Created::SLUG ), 1 );

		$refreshed_order = tec_tc_get_order( $order->ID );

		$this->assertSame( $wp_status_slug_from_slug( Created::SLUG ), $refreshed_order->post_status );
		$this->assertTrue( $has_scheduled_action() );

		as_unschedule_all_actions( 'tec_tickets_commerce_async_webhook_process' );

		$this->assertFalse( $has_scheduled_action() );

		tribe( Order::class )->unlock_order( $order->ID );

		$this->set_class_fn_return( Order::class, 'modify_status', false );
		// Issue is encountered here - Modify status will fail.
		do_action( 'tec_tickets_commerce_async_webhook_process', $order->ID, $wp_status_slug_from_slug( Completed::SLUG ), [], $wp_status_slug_from_slug( Created::SLUG ), 1 );

		$refreshed_order = tec_tc_get_order( $order->ID );

		$this->assertSame( $wp_status_slug_from_slug( Created::SLUG ), $refreshed_order->post_status );
		$this->assertTrue( $has_scheduled_action() );

		as_unschedule_all_actions( 'tec_tickets_commerce_async_webhook_process' );

		$this->assertFalse( $has_scheduled_action() );
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

		tribe( Order::class )->checkout_completed( $order->ID );

		$refreshed_order = tec_tc_get_order( $order->ID );
		$this->assertSame( $wp_status_slug_from_slug( Completed::SLUG ), $refreshed_order->post_status );
		do_action( 'tec_tickets_commerce_async_webhook_process', $order->ID, $wp_status_slug_from_slug( Completed::SLUG ), [], $wp_status_slug_from_slug( Created::SLUG ), 1 );

		$refreshed_order = tec_tc_get_order( $order->ID );

		$this->assertSame( $wp_status_slug_from_slug( Completed::SLUG ), $refreshed_order->post_status );

		$this->assertFalse( as_has_scheduled_action( 'tec_tickets_commerce_async_webhook_process', null, 'tec-tickets-commerce-stripe-webhooks' ) );
	}

	public function test_it_should_throw_exception_when_over_10_tries() {
		$post = self::factory()->post->create(
			[
				'post_type' => 'page',
			]
		);
		$ticket_id_1 = $this->create_tc_ticket( $post, 10 );
		$ticket_id_2 = $this->create_tc_ticket( $post, 20 );

		$wp_status_slug_from_slug = fn( $slug ) => tribe( Status_Handler::class )->get_by_slug( $slug )->get_wp_slug();

		$order = $this->create_order( [ $ticket_id_1 => 1, $ticket_id_2 => 2 ], [ 'order_status' => Created::SLUG ] );

		tribe( Order::class )->checkout_completed( $order->ID );

		$this->assertSame( $wp_status_slug_from_slug( Created::SLUG ), $order->post_status );
		$this->set_class_fn_return( Order::class, 'modify_status', false );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Action failed after too many retries.' );
		do_action( 'tec_tickets_commerce_async_webhook_process', $order->ID, $wp_status_slug_from_slug( Completed::SLUG ), [], $wp_status_slug_from_slug( Created::SLUG ), 10 );
	}
}
