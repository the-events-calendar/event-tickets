<?php

namespace TEC\Tickets\Commerce\Stock;

use TEC\Tickets\Commerce\Module;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Events\Test\Factories\Event;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;

class EventStockTest extends \Codeception\TestCase\WPTestCase {

	use Ticket_Maker;
	use Order_Maker;
	use RSVP_Ticket_Maker;

	public function test_if_provider_is_loaded() {
		$provider = tribe( Module::class );

		$this->assertNotFalse( $provider );
	}

	/**
	 * @test
	 */
	public function test_stock_count_after_single_ticket_creation() {

		$maker    = new Event();
		$event_id = $maker->create();

		// create ticket with default capacity of 100.
		$ticket_a_id = $this->create_tc_ticket( $event_id, 10 );
		// get the ticket.
		$ticket = tribe( Module::class )->get_ticket( $event_id, $ticket_a_id );

		$expected = [];

		$expected['rsvp']    = [
			'count'     => 0,
			'stock'     => 0,
			'unlimited' => 0,
			'available' => 0,
		];
		$expected['tickets'] = [
			'count'     => 1, // count of ticket types currently for sale
			'stock'     => 100, // current stock of tickets available for sale
			'global'    => 0, // numeric boolean if tickets share global stock
			'unlimited' => 0, // numeric boolean if any ticket has unlimited stock
			'available' => 100,
		];

		$counts = \Tribe__Tickets__Tickets::get_ticket_counts( $event_id );
		$this->assertEqualSets( $expected, $counts );
	}

	/**
	 * @test
	 */
	public function test_stock_count_after_multiple_ticket_creation() {

		$maker    = new Event();
		$event_id = $maker->create();

		// create ticket with default capacity of 100.
		$ticket_a_id = $this->create_tc_ticket( $event_id, 10 );
		$ticket_b_id = $this->create_tc_ticket( $event_id, 20 );

		$expected['rsvp']    = [
			'count'     => 0,
			'stock'     => 0,
			'unlimited' => 0,
			'available' => 0,
		];
		$expected['tickets'] = [
			'count'     => 2, // count of ticket types currently for sale
			'stock'     => 200, // current stock of tickets available for sale
			'global'    => 0, // numeric boolean if tickets share global stock
			'unlimited' => 0, // numeric boolean if any ticket has unlimited stock
			'available' => 200,
		];

		$counts = \Tribe__Tickets__Tickets::get_ticket_counts( $event_id );
		$this->assertEqualSets( $expected, $counts );
	}

	/**
	 * @test
	 */
	public function test_stock_count_after_purchase() {

		$maker    = new Event();
		$event_id = $maker->create();

		// create ticket with default capacity of 100.
		$ticket_a_id = $this->create_tc_ticket( $event_id, 10 );

		$order = $this->create_order( [ $ticket_a_id => 5 ] );

		$expected['rsvp']    = [
			'count'     => 0,
			'stock'     => 0,
			'unlimited' => 0,
			'available' => 0,
		];
		$expected['tickets'] = [
			'count'     => 1, // count of ticket types currently for sale
			'stock'     => 95, // current stock of tickets available for sale
			'global'    => 0, // numeric boolean if tickets share global stock
			'unlimited' => 0, // numeric boolean if any ticket has unlimited stock
			'available' => 95,
		];

		$data = \Tribe__Tickets__Tickets::get_ticket_counts( $event_id );

		$this->assertEqualSets( $expected, $data );
	}

	/**
	 * @test
	 */
	public function test_stock_count_after_purchase_of_multiple_tickets() {
		$maker    = new Event();
		$event_id = $maker->create();

		// create ticket with default capacity of 100.
		$ticket_a_id = $this->create_tc_ticket( $event_id, 10 );
		$ticket_b_id = $this->create_tc_ticket( $event_id, 20 );

		$order = $this->create_order(
			[
				$ticket_a_id => 5,
				$ticket_b_id => 10,
			] 
		);

		$expected['rsvp'] = [
			'count'     => 0,
			'stock'     => 0,
			'unlimited' => 0,
			'available' => 0,
		];

		$expected['tickets'] = [
			'count'     => 2, // count of ticket types currently for sale
			'stock'     => 185, // current stock of tickets available for sale
			'global'    => 0, // numeric boolean if tickets share global stock
			'unlimited' => 0, // numeric boolean if any ticket has unlimited stock
			'available' => 185,
		];

		$data = \Tribe__Tickets__Tickets::get_ticket_counts( $event_id );

		$this->assertEqualSets( $expected, $data );
	}

	/**
	 * @test
	 *
	 * Test attendance count with shared capacity tickets.
	 */
	public function test_attendance_count_with_shared_capacity() {

		$maker    = new Event();
		$event_id = $maker->create();

		$overrides   = [
			'tribe-ticket' => [
				'mode'           => \Tribe__Tickets__Global_Stock::CAPPED_STOCK_MODE,
				'event_capacity' => 50,
				'capacity'       => 30,
			],
		];
		$ticket_a_id = $this->create_tc_ticket( $event_id, 10, $overrides );

		$overrides   = [
			'tribe-ticket' => [
				'mode'           => \Tribe__Tickets__Global_Stock::GLOBAL_STOCK_MODE,
				'event_capacity' => 50,
				'capacity'       => 50,
			],
		];
		$ticket_b_id = $this->create_tc_ticket( $event_id, 20, $overrides );

		$expected['rsvp'] = [
			'count'     => 0,
			'stock'     => 0,
			'unlimited' => 0,
			'available' => 0,
		];

		$expected['tickets'] = [
			'count'     => 2, // count of ticket types currently for sale
			'stock'     => 50, // current stock of tickets available for sale
			'global'    => 1, // numeric boolean if tickets share global stock
			'unlimited' => 0, // numeric boolean if any ticket has unlimited stock
			'available' => 50,
		];

		$data = \Tribe__Tickets__Tickets::get_ticket_counts( $event_id );

		// Make sure that we have the proper initial data.
		$this->assertEqualSets( $expected, $data );

		// Create an order for 10 tickets.
		$order = $this->create_order( [ $ticket_a_id => 10 ] );

		// Make sure that the stock count is correct.
		$expected['tickets']['stock']     = 40;
		$expected['tickets']['available'] = 40;

		$data = \Tribe__Tickets__Tickets::get_ticket_counts( $event_id );

		$this->assertEqualSets( $expected, $data );

		// Create an order for 10 tickets.
		$order = $this->create_order( [ $ticket_b_id => 10 ] );

		// Make sure that the stock count is correct.
		$expected['tickets']['stock']     = 30;
		$expected['tickets']['available'] = 30;

		$data = \Tribe__Tickets__Tickets::get_ticket_counts( $event_id );

		$this->assertEqualSets( $expected, $data );
	}

	/**
	 * @test
	 *
	 * Test attendance count with shared capacity tickets and individual capacity tickets.
	 */
	public function test_attendance_count_with_shared_capacity_and_individual_capacity() {

		$maker    = new Event();
		$event_id = $maker->create();

		$overrides   = [
			'tribe-ticket' => [
				'mode'           => \Tribe__Tickets__Global_Stock::CAPPED_STOCK_MODE,
				'event_capacity' => 50,
				'capacity'       => 30,
			],
		];
		$ticket_a_id = $this->create_tc_ticket( $event_id, 10, $overrides );

		$overrides   = [
			'tribe-ticket' => [
				'mode'           => \Tribe__Tickets__Global_Stock::GLOBAL_STOCK_MODE,
				'event_capacity' => 50,
				'capacity'       => 50,
			],
		];
		$ticket_b_id = $this->create_tc_ticket( $event_id, 20, $overrides );

		$overrides   = [
			'tribe-ticket' => [
				'mode'     => \Tribe__Tickets__Global_Stock::OWN_STOCK_MODE,
				'capacity' => 20,
			],
		];
		$ticket_c_id = $this->create_tc_ticket( $event_id, 20, $overrides );

		$expected['rsvp'] = [
			'count'     => 0,
			'stock'     => 0,
			'unlimited' => 0,
			'available' => 0,
		];

		$expected['tickets'] = [
			'count'     => 3, // count of ticket types currently for sale
			'stock'     => 70, // current stock of tickets available for sale
			'global'    => 1, // numeric boolean if tickets share global stock
			'unlimited' => 0, // numeric boolean if any ticket has unlimited stock
			'available' => 70,
		];

		$data = \Tribe__Tickets__Tickets::get_ticket_counts( $event_id );

		// Make sure that we have the proper initial data.
		$this->assertEqualSets( $expected, $data );
	}

	/**
	 * @test Test attendance count with unlimited capacity tickets.
	 */
	public function test_attendance_count_with_unlimited_capacity() {

		$maker    = new Event();
		$event_id = $maker->create();

		$overrides   = [
			'tribe-ticket' => [
				'mode'     => \Tribe__Tickets__Global_Stock::OWN_STOCK_MODE,
				'capacity' => -1,
			],
		];
		$ticket_a_id = $this->create_tc_ticket( $event_id, 10, $overrides );

		$expected['rsvp'] = [
			'count'     => 0,
			'stock'     => 0,
			'unlimited' => 0,
			'available' => 0,
		];

		$expected['tickets'] = [
			'count'     => 1, // count of ticket types currently for sale
			'stock'     => -1, // current stock of tickets available for sale
			'global'    => 0, // numeric boolean if tickets share global stock
			'unlimited' => 1, // numeric boolean if any ticket has unlimited stock
			'available' => -1,
		];

		$data = \Tribe__Tickets__Tickets::get_ticket_counts( $event_id );

		// Make sure that we have the proper initial data.
		$this->assertEqualSets( $expected, $data );
	}

	/**
	 * @test Test attendance count with unlimited capacity tickets.
	 */
	public function test_attendance_count_with_unlimited_capacity_without_shared_cap() {

		$maker    = new Event();
		$event_id = $maker->create();

		$overrides   = [
			'tribe-ticket' => [
				'mode'     => '',
				'capacity' => -1,
			],
		];
		$ticket_a_id = $this->create_tc_ticket( $event_id, 10, $overrides );

		$expected['rsvp'] = [
			'count'     => 0,
			'stock'     => 0,
			'unlimited' => 0,
			'available' => 0,
		];

		$expected['tickets'] = [
			'count'     => 1, // count of ticket types currently for sale
			'stock'     => 0, // current stock of tickets available for sale
			'global'    => 0, // numeric boolean if tickets share global stock
			'unlimited' => 1, // numeric boolean if any ticket has unlimited stock
			'available' => 1,
		];

		$data = \Tribe__Tickets__Tickets::get_ticket_counts( $event_id );

		// Make sure that we have the proper initial data.
		$this->assertEqualSets( $expected, $data );
	}

	/**
	 * @test Test attendance count with RSVP tickets.
	 */
	public function test_attendance_for_rsvp_ticket() {
		$maker    = new Event();
		$event_id = $maker->create();

		$rsvp = $this->create_rsvp_ticket( $event_id );

		$expected['rsvp'] = [
			'count'     => 1,
			'stock'     => 100,
			'unlimited' => 0,
			'available' => 1,
		];

		$expected['tickets'] = [
			'count'     => 0, // count of ticket types currently for sale
			'stock'     => 0, // current stock of tickets available for sale
			'global'    => 0, // numeric boolean if tickets share global stock
			'unlimited' => 0, // numeric boolean if any ticket has unlimited stock
			'available' => 0,
		];

		$data = \Tribe__Tickets__Tickets::get_ticket_counts( $event_id );

		// Make sure that we have the proper initial data.
		$this->assertEqualSets( $expected, $data );
	}

	/**
	 * @test Test attendance count with unlimited tickets.
	 */
	public function test_with_unlimited_rsvp_and_tickets() {
		$maker    = new Event();
		$event_id = $maker->create();

		$overrides = [
			'meta_input' => [
				'_capacity' => -1,
			],
		];

		$rsvp = $this->create_rsvp_ticket( $event_id, $overrides );

		$expected['rsvp'] = [
			'count'     => 1,
			'stock'     => -1,
			'unlimited' => 0,
			'available' => 1,
		];

		$expected['tickets'] = [
			'count'     => 0, // count of ticket types currently for sale
			'stock'     => 0, // current stock of tickets available for sale
			'global'    => 0, // numeric boolean if tickets share global stock
			'unlimited' => 0, // numeric boolean if any ticket has unlimited stock
			'available' => 0,
		];

		$data = \Tribe__Tickets__Tickets::get_ticket_counts( $event_id );

		// Make sure that we have the proper initial data.
		$this->assertEqualSets( $expected, $data );
	}

	/**
	 * @test Test attendance count with unlimited tickets.
	 *
	 * @todo @rafsuntaskin Maybe shift this test to Attendee related test class when available.
	 */
	public function test_created_attendee_has_post_title() {
		$maker    = new Event();
		$event_id = $maker->create();

		// create ticket with default capacity of 100.
		$ticket_a_id = $this->create_tc_ticket( $event_id, 10 );

		$order = $this->create_order(
			[
				$ticket_a_id => 1,
			] 
		);

		$attendee = tec_tc_attendees()->by( 'event_id', $event_id )->first();

		$this->assertNotEmpty( $attendee->post_title );
	}
}
