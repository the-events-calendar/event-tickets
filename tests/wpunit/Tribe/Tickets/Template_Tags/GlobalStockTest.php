<?php

namespace Tribe\Tickets;

use Tribe\Events\Test\Factories\Event;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Order_Maker as PayPal_Order_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe\Tickets\Test\Testcases\Ticket_Object_TestCase;
use Tribe__Tickets__Global_Stock as Global_Stock;
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
	use Attendee_Maker;

	/**
	 * ID of a created TEC Event.
	 *
	 * @see \Tribe\Events\Test\Factories\Event::create_object()
	 *
	 * @var int
	 */
	private $event_id;

	public function setUp() {
		// before
		parent::setUp();

		$this->factory()->event = new Event();
		$this->event_id         = $this->factory()->event->create();
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
		// Enable Global Stock on the Event
		add_post_meta( $this->event_id, Global_Stock::GLOBAL_STOCK_ENABLED, 1 );

		$initial_global_capacity    = 50;
		$paypal_attendees_one_count = 5;
		$paypal_attendees_two_count = 6;

		add_post_meta( $this->event_id, Global_Stock::GLOBAL_STOCK_LEVEL, $initial_global_capacity );

		/**
		 * Create PayPal tickets with global stock enabled with 50 total/shared capacity,
		 * 30 allowable to PayPal One and 40 allowable to PayPal Two.
		 *
		 * @see Tribe__Tickets__Tickets_Handler::has_unlimited_stock() Comments/Explanation.
		 */
		$ticket_ids = $this->create_distinct_paypal_tickets_basic(
			$this->event_id,
			[
				[
					'meta_input' => [
						$this->tickets_handler->key_capacity          => 30,
						'total_sales'                   => $paypal_attendees_one_count,
						Global_Stock::TICKET_STOCK_MODE => Global_Stock::CAPPED_STOCK_MODE,
					],
				],
				[
					'meta_input' => [
						$this->tickets_handler->key_capacity          => 40,
						'total_sales'                   => $paypal_attendees_two_count,
						Global_Stock::TICKET_STOCK_MODE => Global_Stock::CAPPED_STOCK_MODE,
					],
				],
			],
			$initial_global_capacity
		);

		$attendees_count     = $paypal_attendees_one_count + $paypal_attendees_two_count;
		$remaining_available = $initial_global_capacity - $attendees_count;

		$this->assertEquals( $remaining_available, 39, 'Our math is incorrect - check this test!' );
		$this->assertEquals( $remaining_available, tribe_events_count_available_tickets( $this->event_id ), "Incorrect available counts on capped tickets." );

		// Add non-global RSVP ticket (RSVPs don't support Global Stock)
		$initial_rsvp_capacity = 20;
		$rsvp_attendees_count  = 4;

		$rsvp_args = [
			'meta_input' => [
				$this->tickets_handler->key_capacity => $initial_rsvp_capacity,
			],
		];

		$rsvp_id = $this->create_rsvp_ticket( $this->event_id, $rsvp_args );

		// @todo $this->create_many_attendees_for_ticket() for RSVP tickets should update stock/sales counts to avoid needing this, plus can then delete $this->fake_attendee_details() from this class.
		tribe( 'tickets.rsvp' )->generate_tickets_for(
			$rsvp_id,
			$rsvp_attendees_count,
			[
				'full_name'    => 'Jane Doe',
				'email'        => 'jane@doe.com',
				'order_status' => 'yes',
				'optout'       => 'no',
				'order_id'     => RSVP::generate_order_id(),
			]
		);

		$rsvps_going = ( new \Tribe__Tickets__RSVP__Attendance_Totals( $this->event_id ) )->get_total_going();

		$attendees_count     = $rsvps_going + $paypal_attendees_one_count + $paypal_attendees_two_count;
		$remaining_available = $initial_global_capacity + $initial_rsvp_capacity - $attendees_count;

		$this->assertEquals( $remaining_available, 55, 'Our math is incorrect - check this test!' );
		$this->assertEquals( $remaining_available, tribe_events_count_available_tickets( $this->event_id ), "Incorrect available counts on mixed capped tickets and RSVP's." );
	}
}
