<?php

namespace TEC\Tickets\RSVP\V2;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Module;
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

		$this->create_not_going_rsvp_order( $ticket_id, 2 );

		$ticket = Tickets::load_ticket_object( $ticket_id );

		// Not Going attendees do not hold seats: Decrease_Stock bails, so _stock and qty_sold stay at their baseline.
		$this->assertEquals( 50, $ticket->capacity() );
		$this->assertEquals( 50, $ticket->stock() );
		$this->assertEquals( 0, $ticket->qty_sold() );
		$this->assertEquals( 50, $ticket->inventory() );
		$this->assertEquals( 50, $ticket->available() );
	}

	public function test_stock_and_inventory_with_mixed_going_and_not_going(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id, [ 'tribe-ticket' => [ 'capacity' => 50 ] ] );

		// 3 going attendees: a normal Decrease_Stock decrement.
		$this->create_order( [ $ticket_id => 3 ] );

		// 2 not-going attendees: order is created but Decrease_Stock bails for these items.
		$this->create_not_going_rsvp_order( $ticket_id, 2 );

		$ticket = Tickets::load_ticket_object( $ticket_id );

		// 3 going hold seats; 2 not-going do not, so every counter lands at 50 − 3 = 47.
		$this->assertEquals( 50, $ticket->capacity() );
		$this->assertEquals( 47, $ticket->stock() );
		$this->assertEquals( 3, $ticket->qty_sold() );
		$this->assertEquals( 47, $ticket->inventory() );
		$this->assertEquals( 47, $ticket->available() );
	}

	public function test_show_not_going_disabled_does_not_affect_stock_or_inventory(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id, [ 'tribe-ticket' => [ 'capacity' => 50 ] ] );
		update_post_meta( $ticket_id, Constants::SHOW_NOT_GOING_META_KEY, '' );

		// 2 going + 1 not-going.
		$this->create_order( [ $ticket_id => 2 ] );
		$this->create_not_going_rsvp_order( $ticket_id, 1 );

		$ticket = Tickets::load_ticket_object( $ticket_id );

		// show_not_going only controls the UI; counting is unaffected.
		$this->assertEquals( 50, $ticket->capacity() );
		$this->assertEquals( 48, $ticket->stock() );
		$this->assertEquals( 2, $ticket->qty_sold() );
		$this->assertEquals( 48, $ticket->inventory() );
		$this->assertEquals( 48, $ticket->available() );
	}

	public function test_show_not_going_enabled_does_not_affect_stock(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id, [ 'tribe-ticket' => [ 'capacity' => 50 ] ] );
		update_post_meta( $ticket_id, Constants::SHOW_NOT_GOING_META_KEY, '1' );

		// 2 going + 1 not-going.
		$this->create_order( [ $ticket_id => 2 ] );
		$this->create_not_going_rsvp_order( $ticket_id, 1 );

		$ticket = Tickets::load_ticket_object( $ticket_id );

		$this->assertEquals( 50, $ticket->capacity() );
		$this->assertEquals( 48, $ticket->stock() );
		$this->assertEquals( 2, $ticket->qty_sold() );
	}

	/**
	 * Mirrors the production Order_Endpoint flow for Not Going: places a cart item with
	 * type=tc-rsvp + order_status=no (so Decrease_Stock bails) and stamps the rsvp_status
	 * meta on the resulting attendees (so inventory() excludes them).
	 */
	private function create_not_going_rsvp_order( int $ticket_id, int $quantity ): void {
		$order = $this->create_order(
			[
				$ticket_id => [
					'quantity' => $quantity,
					'extras'   => [
						'type'         => Constants::TC_RSVP_TYPE,
						'order_status' => 'no',
					],
				],
			]
		);

		$attendees = tribe( Module::class )->get_attendees_by_order_id( $order->ID );
		foreach ( $attendees as $attendee ) {
			update_post_meta( $attendee['attendee_id'], Constants::RSVP_STATUS_META_KEY, 'no' );
		}
	}
}
