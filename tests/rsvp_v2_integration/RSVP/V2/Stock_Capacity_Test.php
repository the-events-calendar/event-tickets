<?php

namespace TEC\Tickets\RSVP\V2;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Tests\Commerce\RSVP\V2\Ticket_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe__Tickets__Tickets as Tickets;

class Stock_Capacity_Test extends WPTestCase {
	use Ticket_Maker;
	use Order_Maker;

	public function test_stock_and_capacity_with_no_attendees(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id, [ 'tribe-ticket' => [ 'capacity' => 50 ] ] );

		$ticket = Tickets::load_ticket_object( $ticket_id );

		$this->assertEquals( 50, $ticket->capacity() );
		$this->assertEquals( 50, $ticket->stock() );
		$this->assertEquals( 50, $ticket->inventory() );
		$this->assertEquals( 50, $ticket->available() );
		$this->assertEquals( 0, $ticket->qty_sold() );
	}

	public function test_stock_decreases_for_going_attendees(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id, [ 'tribe-ticket' => [ 'capacity' => 50 ] ] );

		$this->create_order( [ $ticket_id => 3 ] );

		$ticket = Tickets::load_ticket_object( $ticket_id );

		$this->assertEquals( 50, $ticket->capacity() );
		$this->assertEquals( 47, $ticket->stock() );
		$this->assertEquals( 47, $ticket->inventory() );
		$this->assertEquals( 47, $ticket->available() );
		$this->assertEquals( 3, $ticket->qty_sold() );
	}

	public function test_stock_and_inventory_with_not_going_attendees(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id, [ 'tribe-ticket' => [ 'capacity' => 50 ] ] );

		$this->create_order( [ $ticket_id => 2 ] );

		// Mark all attendees as not going.
		$attendee_ids = tribe( 'tickets.attendee-repository.rsvp' )
			->by( 'event_id', $post_id )->order_by( 'ID', 'ASC' )->get_ids();
		foreach ( $attendee_ids as $attendee_id ) {
			update_post_meta( $attendee_id, Constants::RSVP_STATUS_META_KEY, 'no' );
		}

		$ticket = Tickets::load_ticket_object( $ticket_id );

		$this->assertEquals( 50, $ticket->capacity() );
		// Stock/sold track raw order counts (decremented at order creation).
		$this->assertEquals( 48, $ticket->stock() );
		$this->assertEquals( 2, $ticket->qty_sold() );
		// Inventory and available also reflect the order-level stock decrease.
		$this->assertEquals( 48, $ticket->inventory() );
		$this->assertEquals( 48, $ticket->available() );
	}

	public function test_stock_and_inventory_with_mixed_going_and_not_going(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id, [ 'tribe-ticket' => [ 'capacity' => 50 ] ] );

		// Create 3 going attendees.
		$this->create_order( [ $ticket_id => 3 ] );

		// Create 2 more attendees, then mark them as not going.
		$this->create_order( [ $ticket_id => 2 ] );
		$all_attendee_ids = tribe( 'tickets.attendee-repository.rsvp' )
			->by( 'event_id', $post_id )->order_by( 'ID', 'DESC' )->get_ids();
		// Mark the 2 most recent as not going.
		$not_going_ids = array_slice( $all_attendee_ids, 0, 2 );
		foreach ( $not_going_ids as $attendee_id ) {
			update_post_meta( $attendee_id, Constants::RSVP_STATUS_META_KEY, 'no' );
		}

		$ticket = Tickets::load_ticket_object( $ticket_id );

		// All 5 attendees consume stock at order creation time, capacity remains unchanged.
		$this->assertEquals( 50, $ticket->capacity() );
		$this->assertEquals( 45, $ticket->stock() );
		$this->assertEquals( 5, $ticket->qty_sold() );
		$this->assertEquals( 45, $ticket->inventory() );
		$this->assertEquals( 45, $ticket->available() );
	}

	public function test_show_not_going_disabled_does_not_affect_stock_or_inventory(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id, [ 'tribe-ticket' => [ 'capacity' => 50 ] ] );
		update_post_meta( $ticket_id, Constants::SHOW_NOT_GOING_META_KEY, '' );

		$this->create_order( [ $ticket_id => 3 ] );
		$attendee_ids = tribe( 'tickets.attendee-repository.rsvp' )
			->by( 'event_id', $post_id )->order_by( 'ID', 'ASC' )->get_ids();
		foreach ( array_slice( $attendee_ids, 0, 1 ) as $attendee_id ) {
			update_post_meta( $attendee_id, Constants::RSVP_STATUS_META_KEY, 'no' );
		}

		$ticket = Tickets::load_ticket_object( $ticket_id );

		// 3 created, 1 not-going — all 3 still consume stock.
		$this->assertEquals( 50, $ticket->capacity() );
		$this->assertEquals( 47, $ticket->stock() );
		$this->assertEquals( 3, $ticket->qty_sold() );
		$this->assertEquals( 47, $ticket->inventory() );
		$this->assertEquals( 47, $ticket->available() );
	}

	public function test_show_not_going_enabled_does_not_affect_stock(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id, [ 'tribe-ticket' => [ 'capacity' => 50 ] ] );
		update_post_meta( $ticket_id, Constants::SHOW_NOT_GOING_META_KEY, '1' );

		$this->create_order( [ $ticket_id => 3 ] );
		$attendee_ids = tribe( 'tickets.attendee-repository.rsvp' )
			->by( 'event_id', $post_id )->order_by( 'ID', 'ASC' )->get_ids();
		foreach ( array_slice( $attendee_ids, 0, 1 ) as $attendee_id ) {
			update_post_meta( $attendee_id, Constants::RSVP_STATUS_META_KEY, 'no' );
		}

		$ticket = Tickets::load_ticket_object( $ticket_id );

		// 3 created, 1 not-going — all 3 still consume stock.
		$this->assertEquals( 50, $ticket->capacity() );
		$this->assertEquals( 47, $ticket->stock() );
		$this->assertEquals( 3, $ticket->qty_sold() );
	}
}
