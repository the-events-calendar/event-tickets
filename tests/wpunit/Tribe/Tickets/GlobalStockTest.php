<?php

namespace Tribe\Tickets;

use Tribe\Events\Test\Factories\Event;
use Tribe\Tickets\Test\Testcases\Ticket_Object_TestCase;
use Tribe__Tickets__Global_Stock as Global_Stock;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Order_Maker as PayPal_Order_Maker;
use Tribe__Tickets__RSVP as RSVP;

/**
 * Test Calculations
 *
 * Class GlobalStockTest
 *
 * @package Tribe\Tickets
 */
class GlobalStockTest extends Ticket_Object_TestCase {

	use PayPal_Ticket_Maker;
	use PayPal_Order_Maker;

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
	 * @test
	 *
	 * @covers ::tribe_events_count_available_tickets()
	 */
	public function it_should_get_correct_event_stock_when_using_global_and_non_global_stock_tickets() {
		$event_id = $this->factory()->event->create();

		// Enable Global Stock on the Event
		add_post_meta( $event_id, Global_Stock::GLOBAL_STOCK_ENABLED, 1 );

		$initial_global_capacity = 50;

		add_post_meta( $event_id, Global_Stock::GLOBAL_STOCK_LEVEL, $initial_global_capacity );

		/**
		 * Create PayPal tickets with global stock enabled with 50 total/shared capacity,
		 * 30 allowable to PayPal One and 40 allowable to PayPal Two.
		 *
		 * @see \Tribe__Tickets__Tickets_Handler::has_unlimited_stock() Comments/Explanation.
		 */
		$paypal_args_one = [
			'meta_input' => [
				'_capacity'                     => $initial_global_capacity - 20,
				Global_Stock::TICKET_STOCK_MODE => Global_Stock::CAPPED_STOCK_MODE,
			],
		];

		// Share the capacity with the other PayPal ticket
		$paypal_args_two = [
			'meta_input' => [
				'_capacity'                     => $initial_global_capacity - 10,
				Global_Stock::TICKET_STOCK_MODE => Global_Stock::CAPPED_STOCK_MODE,
			],
		];

		$paypal_one = $this->create_paypal_ticket( $event_id, 3, $paypal_args_one );
		$paypal_two = $this->create_paypal_ticket( $event_id, 5, $paypal_args_two );

		$paypal_attendees_one_count = 5;
		$paypal_attendees_one       = $this->create_paypal_orders( $event_id, $paypal_one, $paypal_attendees_one_count );

		$paypal_attendees_two_count = 6;
		$paypal_attendees_two       = $this->create_paypal_orders( $event_id, $paypal_two, $paypal_attendees_two_count );

		// Add non-global RSVP ticket (RSVPs don't support Global Stock)
		$initial_rsvp_capacity = 20;

		$rsvp_attendees_count = 4;

		$rsvp_args = [
			'meta_input' => [
				'_capacity' => $initial_rsvp_capacity,
			],
		];

		$rsvp_id = $this->create_rsvp_ticket( $event_id, $rsvp_args );

		// @todo $this->create_many_attendees_for_ticket() for RSVP tickets should update stock/sales counts to avoid needing this, plus can then delete $this->fake_attendee_details() from this class.
		( new RSVP() )->generate_tickets_for( $rsvp_id, $rsvp_attendees_count, $this->fake_attendee_details( [ 'order_status' => 'yes' ] ) );

		$rsvps_going = ( new \Tribe__Tickets__RSVP__Attendance_Totals( $event_id ) )->get_total_going();

		$attendees_count = $rsvps_going + $paypal_attendees_one_count + $paypal_attendees_two_count;

		// 20 + 50 - 4 - 5 - 6 = 55
		$remaining_available = $initial_rsvp_capacity + $initial_global_capacity - $attendees_count;

		$this->assertEquals( $remaining_available, 55 );
		$this->assertEquals( $remaining_available, tribe_events_count_available_tickets( $event_id ) );
	}

	protected function fake_attendee_details( array $overrides = [] ) {
		return array_merge(
			[
				'full_name'    => 'Jane Doe',
				'email'        => 'jane@doe.com',
				'order_status' => 'yes',
				'optout'       => 'no',
				'order_id'     => RSVP::generate_order_id(),
			], $overrides
		);
	}
}