<?php

namespace Tribe\Tickets;

use Tribe\Events\Test\Factories\Event;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\Attendee_Maker as Attendee_Maker;
use Tribe__Tickets__Global_Stock as Global_Stock;
use Tribe\Tickets\Test\Testcases\Ticket_Object_TestCase;

/**
 * Test Calculations
 *
 * Class GlobalStockTest
 *
 * @package Tribe\Tickets
 */
class GlobalStockTest extends Ticket_Object_TestCase {

	public function setUp() {
		// before
		parent::setUp();

		$this->factory()->event = new Event();
	}

	public function tearDown() {
		// your tear down methods here
		// then
		parent::tearDown();
	}

	/**
	 * Available tickets should be correct even if using Global Stock (PayPal) alongside RSVP.
	 * Tribe Commerce PayPal tickets supports global stock, but RSVPs do not.
	 *
	 * @test
	 *
	 * @covers ::tribe_events_count_available_tickets()
	 */
	public function it_should_get_correct_global_stock_availability_alongside_rsvp() {
		$event_id = $this->factory()->event->create();

		$initial_rsvp_capacity = 20;

		// RSVPs (can't have global/shared stock)
		$rsvp_args = [
			tribe( 'tickets.handler' )->key_capacity => $initial_rsvp_capacity,
		];

		$rsvp_id = $this->create_rsvp_ticket( $event_id, $rsvp_args );

		$rsvp_attendees = $this->create_many_attendees_for_ticket( 4, $rsvp_id, $event_id );

		// PayPal with global/shared stock

		$initial_global_capacity = 50;

		/**
		 * Create PayPal tickets with global stock enabled with 50 total/shared capacity.
		 *
		 * @see \Tribe__Tickets__Tickets_Handler::has_unlimited_stock() Comments/Explanation.
		 */
		$paypal_args_one = [
			Global_Stock::GLOBAL_STOCK_ENABLED => 1,
			Global_Stock::GLOBAL_STOCK_LEVEL   => $initial_global_capacity,
			Global_Stock::TICKET_STOCK_MODE    => Global_Stock::GLOBAL_STOCK_MODE,
		];

		// Share the capacity with the other PayPal ticket
		$paypal_args_two = [
			Global_Stock::GLOBAL_STOCK_ENABLED => 1,
			Global_Stock::TICKET_STOCK_MODE    => Global_Stock::CAPPED_STOCK_MODE,
		];

		$paypal_one = $this->create_paypal_ticket( $event_id, 3, $paypal_args_one );
		$paypal_two = $this->create_paypal_ticket( $event_id, 5, $paypal_args_two );

		$paypal_attendees_one = $this->create_many_attendees_for_ticket( 5, $paypal_one, $event_id );
		$paypal_attendees_two = $this->create_many_attendees_for_ticket( 6, $paypal_two, $event_id );

		$all_attendees = array_merge( $rsvp_attendees, $paypal_attendees_one, $paypal_attendees_two );

		// 20 + 50 - 4 - 5 - 6 = 55
		$remaining_available = $initial_rsvp_capacity + $initial_global_capacity - count( $all_attendees );

		$this->assertEquals( $remaining_available, 55 );
		$this->assertEquals( $remaining_available, tribe_events_count_available_tickets( $event_id ) );
	}
}