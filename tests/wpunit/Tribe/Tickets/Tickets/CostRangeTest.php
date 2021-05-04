<?php

namespace Tribe\Tickets\Tickets;

use Tribe\Tickets\Test\Testcases\Ticket_Object_TestCase;

class CostRangeTest extends Ticket_Object_TestCase {

	public function setUp() {
		parent::setUp();

		// making sure the filter is on for the tests.
		add_filter( 'event_tickets_exclude_past_tickets_from_cost_range', '__return_true' );
	}

	/**
	 * It should show costs for all available tickets.
	 *
	 * @test
	 */
	public function should_show_all_costs_of_an_event() {
		$event_id = $this->make_event();

		$ticket1 = $this->create_paypal_ticket( $event_id, 10 );
		$ticket2 = $this->create_paypal_ticket( $event_id, 20 );

		$tickets = \Tribe__Tickets__Tickets::get_event_tickets( $event_id );

		\Tribe__Events__API::update_event_cost( $event_id );

		/** @var \Tribe__Events__Cost_Utils $cost_utils */
		$cost_utils = tribe( 'tec.cost-utils' );

		$costs = $cost_utils->get_event_costs( $event_id );

		$this->assertEquals( 2, count( $costs ) );
	}

	/**
	 * It should hide costs for past tickets.
	 *
	 * @test
	 */
	public function should_show_hide_costs_for_past_tickets() {
		$event_id = $this->make_event();

		$overrides = [
			'ticket_start_date' => '2021-01-01',
			'ticket_end_date'   => '2021-02-02',
		];

		$past_ticket = $this->create_paypal_ticket( $event_id, 20, $overrides );
		$ticket      = $this->create_paypal_ticket( $event_id, 10 );

		\Tribe__Events__API::update_event_cost($event_id);

		$tickets = \Tribe__Tickets__Tickets::get_event_tickets( $event_id );

		/** @var \Tribe__Events__Cost_Utils $cost_utils */
		$cost_utils = tribe( 'tec.cost-utils' );

		$costs = $cost_utils->get_event_costs( $event_id );

		$this->assertEquals( 1, count( $costs ) );
		$this->assertTrue( in_array( 10, $costs ) );
	}

	/**
	 * It should hide costs for future tickets.
	 *
	 * @test
	 */
	public function should_hide_costs_for_future_tickets() {
		$event_id = $this->make_event();

		$overrides = [
			'ticket_start_date' => '2030-01-01',
			'ticket_end_date'   => '2030-02-02',
		];

		$future_ticket = $this->create_paypal_ticket( $event_id, 50, $overrides );
		$ticket        = $this->create_paypal_ticket( $event_id, 10 );
		$ticket        = $this->create_paypal_ticket( $event_id, 20 );
		$ticket        = $this->create_paypal_ticket( $event_id, 30 );

		\Tribe__Events__API::update_event_cost( $event_id );

		$tickets = \Tribe__Tickets__Tickets::get_event_tickets( $event_id );

		/** @var \Tribe__Events__Cost_Utils $cost_utils */
		$cost_utils = tribe( 'tec.cost-utils' );

		$costs = $cost_utils->get_event_costs( $event_id );

		$this->assertEquals( 3, count( $costs ) );
		$this->assertTrue( ! in_array( 50, $costs ) );
	}

	/**
	 * It should not hide any costs if the filter is off.
	 *
	 * @test
	 */
	public function should_not_hide_costs_for_past_events_if_filter_is_off() {
		$event_id = $this->make_event();

		$overrides = [
			'ticket_start_date' => '2021-01-01',
			'ticket_end_date'   => '2021-02-02',
		];

		$past_ticket = $this->create_paypal_ticket( $event_id, 20, $overrides );
		$ticket      = $this->create_paypal_ticket( $event_id, 10 );

		add_filter( 'event_tickets_exclude_past_tickets_from_cost_range', '__return_false' );

		\Tribe__Events__API::update_event_cost( $event_id );

		$tickets = \Tribe__Tickets__Tickets::get_event_tickets( $event_id );

		/** @var \Tribe__Events__Cost_Utils $cost_utils */
		$cost_utils = tribe( 'tec.cost-utils' );

		$costs = $cost_utils->get_event_costs( $event_id );

		$this->assertEquals( 2, count( $costs ) );
		$this->assertTrue( in_array( 10, $costs ) );
		$this->assertTrue( in_array( 20, $costs ) );
	}
}
