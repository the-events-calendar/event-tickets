<?php

namespace TEC\Tickets\Commerce\Stock;

use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Provider;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Gateways\PayPal\Gateway;
use TEC\Tickets\Commerce\Status\Pending;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Events\Test\Factories\Event;

class EventStockTest extends \Codeception\TestCase\WPTestCase {

	use Ticket_Maker;
	use Order_Maker;

	/**
	 * @inheritDoc
	 */
	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();

		add_filter( 'tribe_tickets_ticket_object_is_ticket_cache_enabled', '__return_false' );
	}

	public function test_if_provider_is_loaded() {
		$provider = tribe( Module::class );

		$this->assertNotFalse( $provider );
	}

	/**
	 * @test
	 */
	public function test_stock_count_after_single_ticket_creation() {

		$maker = new Event();
		$event_id = $maker->create();

		// create ticket with default capacity of 100.
		$ticket_a_id = $this->create_tc_ticket( $event_id, 10 );
		// get the ticket.
		$ticket = tribe( Module::class )->get_ticket( $event_id, $ticket_a_id );

		$expected = [];

		$expected['rsvp'] = [
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
			'available' => 1,
		];

		$counts = \Tribe__Tickets__Tickets::get_ticket_counts( $event_id );
		$this->assertEqualSets( $expected, $counts );
	}

	/**
	 * @test
	 */
	public function test_stock_count_after_multiple_ticket_creation() {

		$maker = new Event();
		$event_id = $maker->create();

		// create ticket with default capacity of 100.
		$ticket_a_id = $this->create_tc_ticket( $event_id, 10 );
		$ticket_b_id = $this->create_tc_ticket( $event_id, 20 );

		$expected['rsvp'] = [
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
			'available' => 2,
		];

		$counts = \Tribe__Tickets__Tickets::get_ticket_counts( $event_id );
		$this->assertEqualSets( $expected, $counts );
	}

	/**
	 * @test
	 */
	public function test_stock_count_after_purchase() {

		$maker = new Event();
		$event_id = $maker->create();

		// create ticket with default capacity of 100.
		$ticket_a_id = $this->create_tc_ticket( $event_id, 10 );

		$order = $this->create_order( [ $ticket_a_id => 5 ] );

		$expected['rsvp'] = [
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
			'available' => 1,
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

		$order = $this->create_order( [
			$ticket_a_id => 5,
			$ticket_b_id => 10,
		] );

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
			'available' => 2,
		];

		$data = \Tribe__Tickets__Tickets::get_ticket_counts( $event_id );

		$this->assertEqualSets( $expected, $data );
	}
}