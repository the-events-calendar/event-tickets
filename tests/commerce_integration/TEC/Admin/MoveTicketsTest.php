<?php

namespace TEC\Admin;

use Tribe\Events\Test\Factories\Event;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use TEC\Tickets\Commerce\Module;

class MoveTicketsTest extends \Codeception\TestCase\WPTestCase {
	use Ticket_Maker;
	use Order_Maker;

	/**
	 * Test the Move_Tickets functionality.
	 *
	 * @test
	 */
	public function move_tickets_between_events_with_capped_stock_mode() {

		// Create two new events.
		$maker      = new Event();
		$event_1_id = $maker->create();
		$event_2_id = $maker->create();

		// Create a ticket for the first event with capped stock mode and specific capacities.
		$overrides      = [
			'tribe-ticket' => [
				'mode'           => \Tribe__Tickets__Global_Stock::CAPPED_STOCK_MODE,
				'event_capacity' => 50,
				'capacity'       => 30,
			],
		];
		$event_1_ticket = $this->create_tc_ticket( $event_1_id, 10, $overrides );

		// Create a ticket for the second event with global stock mode and specific capacities.
		$overrides      = [
			'tribe-ticket' => [
				'mode'           => \Tribe__Tickets__Global_Stock::GLOBAL_STOCK_MODE,
				'event_capacity' => 50,
				'capacity'       => 50,
			],
		];
		$event_2_ticket = $this->create_tc_ticket( $event_2_id, 20, $overrides );

		// Create an order for 5 tickets of the first event.
		$order = $this->create_order( [ $event_1_ticket => 5 ] );

		// Fetch the attendees for the first event ticket.
		tribe_cache()->delete( 'tec_tickets_attendees_by_ticket_id' );
		$attendees_objects = tribe_tickets_get_ticket_provider( $event_1_ticket )->get_attendees_by_id( $event_1_ticket );
		// Assert that the attendees_objects array is not empty.
		$this->assertNotEmpty( $attendees_objects, 'The attendees_objects array should not be empty.' );

		// Assert that the first item in the array has the 'ID' key.
		$this->assertArrayHasKey( 'ID', $attendees_objects[0], 'The first item in the array should have an "ID" key.' );

		/**
		 * Our goal is to move a single ticket from Event 1 to Event 2.
		 */
		// Move the first ticket from the first event to the second event.
		$successful_moves = tribe( 'Tribe__Tickets__Admin__Move_Tickets' )->move_tickets(
			[ $attendees_objects[0]['ID'] ],
			$event_2_ticket,
			$event_1_id,
			$event_2_id
		);

		// Assert that the move was successful.
		$this->assertEquals( 1, $successful_moves, 'The ticket move operation should be successful.' );

		tribe_cache()->delete( 'tec_tickets_attendees_by_ticket_id' );

		// refresh the ticket objects.
		$ticket_event_1 = tribe( Module::class )->get_ticket( $event_1_id, $event_1_ticket );
		$ticket_event_2 = tribe( Module::class )->get_ticket( $event_2_id, $event_2_ticket );

		$this->assertEquals(
			25 + 1,
			$ticket_event_1->inventory(),
			'Inventory for ticket 1 should be greater than original value.'
		);
		$this->assertEquals(
			25 + 1,
			$ticket_event_1->stock(),
			'Stock for ticket 1 should be greater than original value.'
		);
		$this->assertEquals(
			50 - 1,
			$ticket_event_2->available(),
			'Available tickets for ticket 2 should be less than original value.'
		);
		$this->assertEquals(
			50 - 1,
			$ticket_event_2->inventory(),
			'Inventory for ticket 2 should be less than original value.'
		);
	}

	/**
	 * Test moving an invalid ticket ID.
	 *
	 * @test
	 */
	public function move_tickets_should_return_0_for_invalid_data() {
		// Try moving with invalid ID's
		$successful_moves = tribe( 'Tribe__Tickets__Admin__Move_Tickets' )->move_tickets(
			[ 999999 ],
			1,
			1,
			1
		);

		// Assert that the move was not successful.
		$this->assertEquals(
			0,
			$successful_moves,
			'The ticket move operation for an invalid ticket ID should not be successful.'
		);
	}

	/**
	 * Move Tickets between the same event using capped stock mode
	 *
	 * @test
	 */
	public function move_tickets_between_same_event_with_capped_Stock_mode() {

		// Create two new events.
		$maker      = new Event();
		$event_1_id = $maker->create();

		// Create a ticket for the first event with capped stock mode and specific capacities.
		$overrides        = [
			'tribe-ticket' => [
				'mode'           => \Tribe__Tickets__Global_Stock::CAPPED_STOCK_MODE,
				'event_capacity' => 50,
				'capacity'       => 30,
			],
		];
		$event_1_ticket_1 = $this->create_tc_ticket( $event_1_id, 10, $overrides );
		$event_1_ticket_2 = $this->create_tc_ticket( $event_1_id, 10, $overrides );


		// Create an order for 5 tickets of the first event.
		$order = $this->create_order( [ $event_1_ticket_1 => 5 ] );

		// Fetch the attendees for the first event ticket.
		tribe_cache()->delete( 'tec_tickets_attendees_by_ticket_id' );
		$attendees_objects = tribe_tickets_get_ticket_provider( $event_1_ticket_1 )->get_attendees_by_id( $event_1_ticket_1 );
		// Assert that the attendees_objects array is not empty.
		$this->assertNotEmpty( $attendees_objects, 'The attendees_objects array should not be empty.' );

		// Assert that the first item in the array has the 'ID' key.
		$this->assertArrayHasKey( 'ID', $attendees_objects[0], 'The first item in the array should have an "ID" key.' );


		$successful_moves = tribe( 'Tribe__Tickets__Admin__Move_Tickets' )->move_tickets(
			[ $attendees_objects[0]['ID'] ],
			$event_1_ticket_2,
			$event_1_id,
			$event_1_id
		);

		// Assert that the move was successful.
		$this->assertEquals( 1, $successful_moves, 'The ticket move operation should be successful.' );

		tribe_cache()->delete( 'tec_tickets_attendees_by_ticket_id' );

		$ticket_event_1_ticket_1 = tribe( Module::class )->get_ticket( $event_1_id, $event_1_ticket_1 );
		$ticket_event_1_ticket_2 = tribe( Module::class )->get_ticket( $event_1_id, $event_1_ticket_2 );

		$this->assertEquals(
			25 + 1,
			$ticket_event_1_ticket_1->inventory(),
			'Inventory for ticket 1 should be greater than original value.'
		);
		$this->assertEquals(
			25 + 1,
			$ticket_event_1_ticket_1->stock(),
			'Stock for ticket 1 should be greater than original value.'
		);

		$this->assertEquals(
			30 - 1,
			$ticket_event_1_ticket_2->inventory(),
			'Inventory for ticket 2 should be less than original value.'
		);
		$this->assertEquals(
			30 - 1,
			$ticket_event_1_ticket_2->stock(),
			'Stock for ticket 2 should be less than original value.'
		);

	}

	/**
	 * Move Tickets between the same event
	 *
	 * @test
	 */
	public function move_tickets_between_same_event_with_global_Stock_mode() {
		// Create two new events.
		$event_1_id = tribe_events()->set_args(
			[
				'title'      => 'TEst Event',
				'status'     => 'publish',
				'start_date' => '2022-10-31 10:00:00',
				'duration'   => 4 * HOUR_IN_SECONDS,
			]
		)->create()->ID;
		// Set the Event shared capacity to 50.
		update_post_meta( $event_1_id, \Tribe__Tickets__Tickets_Handler::instance()->key_capacity, 50 );

		// Create a ticket for the first event with capped stock mode and specific capacities.
		$event_1_ticket_1 = $this->create_tc_ticket(
			$event_1_id,
			10,
			[
				'tribe-ticket' => [
					'mode'     => \Tribe__Tickets__Global_Stock::CAPPED_STOCK_MODE,
					'capacity' => 30,
				],
			]
		);
		$event_1_ticket_2 = $this->create_tc_ticket(
			$event_1_id,
			10,
			[
				'tribe-ticket' => [
					'mode'     => \Tribe__Tickets__Global_Stock::CAPPED_STOCK_MODE,
					'capacity' => 30,
				],
			]
		);

		$this->assertEquals( 30, tribe( Module::class )->get_ticket( $event_1_id, $event_1_ticket_1 )->inventory() );
		$this->assertEquals( 30, tribe( Module::class )->get_ticket( $event_1_id, $event_1_ticket_2 )->inventory() );

		// Create an order for 5 tickets of the first event.
		$order = $this->create_order( [ $event_1_ticket_1 => 5 ] );

		tribe_cache()->delete( 'tec_tickets_attendees_by_ticket_id' );

		$this->assertEquals( 25, tribe( Module::class )->get_ticket( $event_1_id, $event_1_ticket_1 )->inventory() );
		$this->assertEquals( 30, tribe( Module::class )->get_ticket( $event_1_id, $event_1_ticket_2 )->inventory() );

		// Fetch the attendees for the first event ticket.
		tribe_cache()->delete( 'tec_tickets_attendees_by_ticket_id' );
		$attendees_objects = tribe_tickets_get_ticket_provider( $event_1_ticket_1 )->get_attendees_by_id( $event_1_ticket_1 );
		// Assert that the attendees_objects array is not empty.
		$this->assertNotEmpty( $attendees_objects, 'The attendees_objects array should not be empty.' );

		// Assert that the first item in the array has the 'ID' key.
		$this->assertArrayHasKey( 'ID', $attendees_objects[0], 'The first item in the array should have an "ID" key.' );

		$successful_moves = tribe( \Tribe__Tickets__Admin__Move_Tickets::class )->move_tickets(
			[ $attendees_objects[0]['ID'] ],
			$event_1_ticket_2,
			$event_1_id,
			$event_1_id
		);

		// Assert that the move was successful.
		$this->assertEquals( 1, $successful_moves, 'The ticket move operation should be successful.' );

		tribe_cache()->delete( 'tec_tickets_attendees_by_ticket_id' );

		$ticket_event_1_ticket_1 = tribe( Module::class )->get_ticket( $event_1_id, $event_1_ticket_1 );
		$ticket_event_1_ticket_2 = tribe( Module::class )->get_ticket( $event_1_id, $event_1_ticket_2 );

		$this->assertEquals(
			26,
			$ticket_event_1_ticket_1->inventory(),
			'Inventory for ticket 1 should be greater than original value.'
		);
		$this->assertEquals(
			26,
			$ticket_event_1_ticket_1->stock(),
			'Stock for ticket 1 should be greater than original value.'
		);

		$this->assertEquals(
			29,
			$ticket_event_1_ticket_2->inventory(),
			'Inventory for ticket 2 should be less than original value.'
		);
		$this->assertEquals(
			29,
			$ticket_event_1_ticket_2->stock(),
			'Stock for ticket 2 should be less than original value.'
		);
	}

	/**
	 * Move Tickets between the same event using regular tickets
	 *
	 * @test
	 */
	public function move_tickets_between_same_event_with_regular_ticket() {
		// Create two new events.
		$maker      = new Event();
		$event_1_id = $maker->create();

		// Create a ticket for the first event with capped stock mode and specific capacities.
		$overrides        = [
			'tribe-ticket' => [
				'event_capacity' => 50,
				'capacity'       => 30,
			],
		];
		$event_1_ticket_1 = $this->create_tc_ticket( $event_1_id, 10, $overrides );
		$event_1_ticket_2 = $this->create_tc_ticket( $event_1_id, 10, $overrides );


		// Create an order for 5 tickets of the first event.
		$order = $this->create_order( [ $event_1_ticket_1 => 5 ] );

		// Fetch the attendees for the first event ticket.
		tribe_cache()->delete( 'tec_tickets_attendees_by_ticket_id' );
		$attendees_objects = tribe_tickets_get_ticket_provider( $event_1_ticket_1 )->get_attendees_by_id( $event_1_ticket_1 );
		// Assert that the attendees_objects array is not empty.
		$this->assertNotEmpty( $attendees_objects, 'The attendees_objects array should not be empty.' );

		// Assert that the first item in the array has the 'ID' key.
		$this->assertArrayHasKey( 'ID', $attendees_objects[0], 'The first item in the array should have an "ID" key.' );

		$successful_moves = tribe( 'Tribe__Tickets__Admin__Move_Tickets' )->move_tickets(
			[ $attendees_objects[0]['ID'] ],
			$event_1_ticket_2,
			$event_1_id,
			$event_1_id
		);

		// Assert that the move was successful.
		$this->assertEquals( 1, $successful_moves, 'The ticket move operation should be successful.' );

		tribe_cache()->delete( 'tec_tickets_attendees_by_ticket_id' );

		$ticket_event_1_ticket_1 = tribe( Module::class )->get_ticket( $event_1_id, $event_1_ticket_1 );
		$ticket_event_1_ticket_2 = tribe( Module::class )->get_ticket( $event_1_id, $event_1_ticket_2 );

		$this->assertEquals(
			25 + 1,
			$ticket_event_1_ticket_1->inventory(),
			'Inventory for ticket 1 should be greater than original value.'
		);
		$this->assertEquals(
			25 + 1,
			$ticket_event_1_ticket_1->stock(),
			'Stock should same as inventory.'
		);

		$this->assertEquals(
			30 - 1,
			$ticket_event_1_ticket_2->inventory(),
			'Inventory for ticket 2 should be less than original value.'
		);
		$this->assertEquals(
			30 - 1,
			$ticket_event_1_ticket_2->stock(),
			'Stock should be same as inventory.'
		);
	}

	/**
	 * Move Tickets between the same event using capped stock mode
	 *
	 * @test
	 */
	public function move_tickets_between_same_event_with_one_shared_one_regular() {

		// Create two new events.
		$maker      = new Event();
		$event_1_id = $maker->create();

		// Create a ticket for the first event with capped stock mode and specific capacities.
		$overrides        = [
			'tribe-ticket' => [
				'mode'           => \Tribe__Tickets__Global_Stock::CAPPED_STOCK_MODE,
				'event_capacity' => 50,
				'capacity'       => 30,
			],
		];
		$event_1_ticket_1 = $this->create_tc_ticket( $event_1_id, 10, $overrides );
		// Create a ticket for the first event with capped stock mode and specific capacities.
		$overrides        = [
			'tribe-ticket' => [
				'event_capacity' => 50,
				'capacity'       => 30,
			],
		];
		$event_1_ticket_2 = $this->create_tc_ticket( $event_1_id, 10, $overrides );


		// Create an order for 5 tickets of the first event.
		$order = $this->create_order( [ $event_1_ticket_1 => 5 ] );

		// Fetch the attendees for the first event ticket.
		tribe_cache()->delete( 'tec_tickets_attendees_by_ticket_id' );
		$attendees_objects = tribe_tickets_get_ticket_provider( $event_1_ticket_1 )->get_attendees_by_id( $event_1_ticket_1 );
		// Assert that the attendees_objects array is not empty.
		$this->assertNotEmpty( $attendees_objects, 'The attendees_objects array should not be empty.' );

		// Assert that the first item in the array has the 'ID' key.
		$this->assertArrayHasKey( 'ID', $attendees_objects[0], 'The first item in the array should have an "ID" key.' );


		$successful_moves = tribe( 'Tribe__Tickets__Admin__Move_Tickets' )->move_tickets(
			[ $attendees_objects[0]['ID'] ],
			$event_1_ticket_2,
			$event_1_id,
			$event_1_id
		);

		// Assert that the move was successful.
		$this->assertEquals( 1, $successful_moves, 'The ticket move operation should be successful.' );

		tribe_cache()->delete( 'tec_tickets_attendees_by_ticket_id' );

		$ticket_event_1_ticket_1 = tribe( Module::class )->get_ticket( $event_1_id, $event_1_ticket_1 );
		$ticket_event_1_ticket_2 = tribe( Module::class )->get_ticket( $event_1_id, $event_1_ticket_2 );

		$this->assertEquals(
			25 + 1,
			$ticket_event_1_ticket_1->inventory(),
			'Inventory for ticket 1 should be greater than original value.'
		);
		$this->assertEquals(
			25 + 1,
			$ticket_event_1_ticket_1->stock(),
			'Stock for ticket 1 should be greater than original value.'
		);

		$this->assertEquals(
			30 - 1,
			$ticket_event_1_ticket_2->inventory(),
			'Inventory for ticket 2 should be less than original value.'
		);
		$this->assertEquals(
			30 - 1,
			$ticket_event_1_ticket_2->stock(),
			'Stock for ticket 2 should be less than original value.'
		);
	}
}
