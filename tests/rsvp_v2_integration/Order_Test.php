<?php

namespace TECTicketsRSVPV2Tests;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Order as TC_Order;
use TEC\Tickets\RSVP\V2\Attendee;
use TEC\Tickets\RSVP\V2\Cart\RSVP_Cart;
use TEC\Tickets\RSVP\V2\Order;
use TEC\Tickets\Test\Commerce\RSVP\V2\Ticket_Maker;
use WP_Error;

class Order_Test extends WPTestCase {
	use Ticket_Maker;

	public function test_should_return_error_for_empty_cart(): void {
		$cart  = new RSVP_Cart();
		$order = tribe( Order::class );

		$result = $order->create( $cart, [
			'name'  => 'John Doe',
			'email' => 'john@example.com',
		] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'tec_tickets_rsvp_v2_empty_cart', $result->get_error_code() );
	}

	public function test_should_return_error_for_missing_purchaser_name(): void {
		$event_id  = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $event_id );

		$cart = new RSVP_Cart();
		$cart->set_hash( 'test-hash' );
		$cart->upsert_item( $ticket_id, 1 );

		$order = tribe( Order::class );

		$result = $order->create( $cart, [
			'email' => 'john@example.com',
		] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'tec_tickets_rsvp_v2_missing_purchaser', $result->get_error_code() );
	}

	public function test_should_return_error_for_missing_purchaser_email(): void {
		$event_id  = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $event_id );

		$cart = new RSVP_Cart();
		$cart->set_hash( 'test-hash' );
		$cart->upsert_item( $ticket_id, 1 );

		$order = tribe( Order::class );

		$result = $order->create( $cart, [
			'name' => 'John Doe',
		] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'tec_tickets_rsvp_v2_missing_purchaser', $result->get_error_code() );
	}

	public function test_get_returns_order_post(): void {
		$order_class = tribe( Order::class );

		// Create a mock order to test get().
		$order_id = wp_insert_post( [
			'post_type'   => TC_Order::POSTTYPE,
			'post_status' => 'publish',
			'post_title'  => 'Test Order',
		] );

		$order = $order_class->get( $order_id );

		$this->assertNotNull( $order );
		$this->assertSame( $order_id, $order->ID );
	}

	public function test_get_returns_null_for_invalid_order(): void {
		$order = tribe( Order::class );

		$result = $order->get( 999999 );

		$this->assertNull( $result );
	}

	public function test_get_attendees_returns_attendees_for_order(): void {
		$event_id   = static::factory()->post->create();
		$ticket_id  = $this->create_rsvp_ticket( $event_id );
		$order_id   = wp_insert_post( [
			'post_type'   => TC_Order::POSTTYPE,
			'post_status' => 'publish',
			'post_title'  => 'Test Order',
		] );

		// Create attendees for this order.
		$attendee = tribe( Attendee::class );
		$attendee->create( $order_id, $ticket_id, [
			'event_id' => $event_id,
			'name'     => 'Person 1',
		] );
		$attendee->create( $order_id, $ticket_id, [
			'event_id' => $event_id,
			'name'     => 'Person 2',
		] );

		$order_class = tribe( Order::class );
		$attendees = $order_class->get_attendees( $order_id );

		$this->assertCount( 2, $attendees );
	}

	public function test_get_attendees_returns_empty_for_order_without_attendees(): void {
		$order_id = wp_insert_post( [
			'post_type'   => TC_Order::POSTTYPE,
			'post_status' => 'publish',
			'post_title'  => 'Test Order',
		] );

		$order     = tribe( Order::class );
		$attendees = $order->get_attendees( $order_id );

		$this->assertEmpty( $attendees );
	}
}
