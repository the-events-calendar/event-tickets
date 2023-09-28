<?php

namespace TEC\Admin;

use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Gateways\PayPal\Gateway;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Ticket;
use TEC\Tickets\Commerce\Provider;
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Pending;
use Tribe\Events\Test\Factories\Event;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe__Tickets__Tickets;

class MoveTicketsTest extends \Codeception\TestCase\WPTestCase {
	use Ticket_Maker;
	use Order_Maker;

	/**
	 * Test the shared capacity purchase functionality.
	 */
	public function test_tc_shared_capacity_purchase() {

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
		$this->assertEquals(1, $successful_moves, 'The ticket move operation should be successful.' );
	}

}