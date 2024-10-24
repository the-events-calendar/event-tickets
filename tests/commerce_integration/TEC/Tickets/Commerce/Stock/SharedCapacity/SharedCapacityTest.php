<?php

namespace TEC\Tickets\Commerce\Stock\SharedCapacity;

use TEC\Tickets\Commerce\Module;
use Tribe\Events\Test\Factories\Event;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe__Events__Main as TEC;
use Tribe__Tickets__Global_Stock as Global_Stock;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use Tribe__Tickets__Tickets_Handler as Tickets_Handler;

/**
 * Class SharedCapacityTest
 *
 * @package TEC\Tickets\Commerce\Stock\SharedCapacity
 */
class SharedCapacityTest extends \Codeception\TestCase\WPTestCase {

	use Ticket_Maker;
	use With_Uopz;
	use Order_Maker;

	/**
	 * @before
	 */
	public function ensure_post_ticketable(): void {
		$ticketable   = tribe_get_option( 'ticket-enabled-post-types', [] );
		$ticketable[] = 'post';
		$ticketable[] = TEC::POSTTYPE;
		tribe_update_option( 'ticket-enabled-post-types', array_values( array_unique( $ticketable ) ) );
	}

	public function test_tc_shared_capacity_purchase() {
		$event_id = tribe_events()->set_args(
			[
				'title'      => 'Test Event',
				'status'     => 'publish',
				'start_date' => '2020-01-01 12:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;
		// Enable the global stock on the Event.
		update_post_meta( $event_id, Global_Stock::GLOBAL_STOCK_ENABLED, 1 );
		// Set the Event global stock level to 50.
		update_post_meta( $event_id, Global_Stock::GLOBAL_STOCK_LEVEL, 50 );

		$ticket_a_id = $this->create_tc_ticket(
			$event_id,
			10,
			[
				'tribe-ticket' => [
					'mode'     => Global_Stock::CAPPED_STOCK_MODE,
					'capacity' => 30,
				],
			]
		);
		$ticket_b_id = $this->create_tc_ticket(
			$event_id,
			20,
			[
				'tribe-ticket' => [
					'mode'     => Global_Stock::GLOBAL_STOCK_MODE,
					'capacity' => 50,
				],
			]
		);

		// Get the ticket objects.
		$ticket_a = tribe( Module::class )->get_ticket( $event_id, $ticket_a_id );
		$ticket_b = tribe( Module::class )->get_ticket( $event_id, $ticket_b_id );

		// Make sure both tickets are valid Ticket Object.
		$this->assertInstanceOf( Ticket_Object::class, $ticket_a );
		$this->assertInstanceOf( Ticket_Object::class, $ticket_b );

		$this->assertEquals( 30, $ticket_a->capacity() );
		$this->assertEquals( 30, $ticket_a->stock() );
		$this->assertEquals( 30, $ticket_a->available() );
		$this->assertEquals( 30, $ticket_a->inventory() );

		$this->assertEquals( 50, $ticket_b->capacity() );
		$this->assertEquals( 50, $ticket_b->stock() );
		$this->assertEquals( 50, $ticket_b->available() );
		$this->assertEquals( 50, $ticket_b->inventory() );

		$global_stock = new Global_Stock( $event_id );

		$this->assertTrue( $global_stock->is_enabled(), 'Global stock should be enabled.' );
		$this->assertEquals( 50, tribe_get_event_capacity( $event_id ), 'Total Event capacity should be 50' );
		$this->assertEquals( 50, $global_stock->get_stock_level(), 'Global stock should be 50' );

		// Create an Order for 5 on each Ticket.
		$order = $this->create_order(
			[
				$ticket_a_id => 5,
				$ticket_b_id => 5,
			]
		);

		tribe_cache()->delete( 'tec_tickets_attendees_by_ticket_id' );

		// Refresh the ticket objects.
		$ticket_a = tribe( Module::class )->get_ticket( $event_id, $ticket_a_id );
		$ticket_b = tribe( Module::class )->get_ticket( $event_id, $ticket_b_id );

		$this->assertEquals( 30, $ticket_a->capacity() );
		$this->assertEquals( 30 - 5, $ticket_a->stock() );
		$this->assertEquals( 30 - 5, $ticket_a->available() );
		$this->assertEquals( 30 - 5, $ticket_a->inventory() );

		$this->assertEquals( 50, $ticket_b->capacity() );
		$this->assertEquals( 50 - 10, $ticket_b->stock() );
		$this->assertEquals( 50 - 10, $ticket_b->available() );
		$this->assertEquals( 50 - 10, $ticket_b->inventory() );

		$this->assertEquals( 50 - 10, $global_stock->get_stock_level(), 'Global stock should be 50-10 = 40' );
	}

	public function test_tc_total_shared_capacity_decreased_after_purchase() {
		$event_id = tribe_events()->set_args(
			[
				'title'      => 'Test Event',
				'status'     => 'publish',
				'start_date' => '2020-01-01 12:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;
		// Enable the global stock on the Event.
		update_post_meta( $event_id, Global_Stock::GLOBAL_STOCK_ENABLED, 1 );
		// Set the Event global stock level to 20.
		update_post_meta( $event_id, Global_Stock::GLOBAL_STOCK_LEVEL, 20 );

		$ticket_id = $this->create_tc_ticket(
			$event_id,
			20,
			[
				'tribe-ticket' => [
					'mode'     => Global_Stock::GLOBAL_STOCK_MODE,
					'capacity' => 20,
				],
			]
		);

		// Get the ticket object.
		$ticket = tribe( Module::class )->get_ticket( $event_id, $ticket_id );

		// Make sure it's a valid Ticket Object.
		$this->assertInstanceOf( Ticket_Object::class, $ticket );

		// Create order for Global stock ticket.
		$order = $this->create_order( [ $ticket_id => 5 ] );

		// Refresh the ticket objects.
		$ticket       = tribe( Module::class )->get_ticket( $event_id, $ticket_id );
		$global_stock = new Global_Stock( $event_id );

		$this->assertEquals( 20, $ticket->capacity() );
		$this->assertEquals( 20 - 5, $ticket->stock() );
		$this->assertEquals( 20 - 5, $ticket->available() );
		$this->assertEquals( 20 - 5, $ticket->inventory() );
		$this->assertEquals( 20 - 5, $global_stock->get_stock_level() );

		$new_global_capacity = 10;
		/** @var Tickets_Handler $handler */
		$handler = tribe( 'tickets.handler' );
		$handler->sync_shared_capacity( $event_id, $new_global_capacity );
		// Update the Event's capacity manually.
		tribe_tickets_update_capacity( $event_id, $new_global_capacity );

		// Refresh the ticket objects.
		$ticket = tribe( Module::class )->get_ticket( $event_id, $ticket_id );

		$this->assertEquals( $new_global_capacity, $ticket->capacity(), 'Ticket Capacity should be 10' );
		$this->assertEquals( $new_global_capacity, tribe_tickets_get_capacity( $ticket_id ), 'Ticket Capacity should be 10' );
		$this->assertEquals( $new_global_capacity - 5, $ticket->stock(), 'Ticket Stock should be 5' );
		$this->assertEquals( $new_global_capacity - 5, $ticket->available(), 'Ticket available should be 5' );
		$this->assertEquals( $new_global_capacity - 5, $ticket->inventory(), 'Ticket inventory should be 5' );
		$this->assertEquals( $new_global_capacity - 5, $global_stock->get_stock_level(), 'Global stock level should be 5' );
	}

	public function test_tc_total_shared_capacity_increased_after_purchase() {
		$event_id = tribe_events()->set_args(
			[
				'title'      => 'Test Event',
				'status'     => 'publish',
				'start_date' => '2020-01-01 12:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;
		// Enable the global stock on the Event.
		update_post_meta( $event_id, Global_Stock::GLOBAL_STOCK_ENABLED, 1 );
		// Set the Event global stock level to 20.
		update_post_meta( $event_id, Global_Stock::GLOBAL_STOCK_LEVEL, 20 );

		$ticket_id = $this->create_tc_ticket(
			$event_id,
			20,
			[
				'tribe-ticket' => [
					'mode'           => Global_Stock::GLOBAL_STOCK_MODE,
					'event_capacity' => 20,
					'capacity'       => 20,
				],
			]
		);

		// Get the ticket objects.
		$ticket = tribe( Module::class )->get_ticket( $event_id, $ticket_id );

		// Make sure both tickets are valid Ticket Object.
		$this->assertInstanceOf( Ticket_Object::class, $ticket );

		// Create order for Global stock ticket.
		$order = $this->create_order( [ $ticket_id => 5 ] );
		// Refresh the ticket objects.
		$ticket = tribe( Module::class )->get_ticket( $event_id, $ticket_id );

		$this->assertEquals( 20, $ticket->capacity() );
		$this->assertEquals( 20 - 5, $ticket->stock() );
		$this->assertEquals( 20 - 5, $ticket->available() );
		$this->assertEquals( 20 - 5, $ticket->inventory() );

		$new_global_capacity = 30;

		/** @var Tickets_Handler $handler */
		$handler = tribe( 'tickets.handler' );
		$handler->sync_shared_capacity( $event_id, $new_global_capacity );
		// update the Event's capacity manually.
		tribe_tickets_update_capacity( $event_id, $new_global_capacity );

		// refresh the ticket objects.
		$ticket = tribe( Module::class )->get_ticket( $event_id, $ticket_id );

		$this->assertEquals( $new_global_capacity, $ticket->capacity(), 'Ticket Capacity should be 30' );
		$this->assertEquals( $new_global_capacity, tribe_tickets_get_capacity( $ticket_id ), 'Ticket Capacity should be 30' );
		$this->assertEquals( $new_global_capacity - 5, $ticket->stock(), 'Ticket Stock should be 25' );
		$this->assertEquals( $new_global_capacity - 5, $ticket->available(), 'Ticket available should be 25' );
		$this->assertEquals( $new_global_capacity - 5, $ticket->inventory(), 'Ticket inventory should be 25' );
	}

	public function test_tc_total_shared_capacity_increase_should_not_impact_capped_capacity() {
		$event_id = tribe_events()->set_args(
			[
				'title'      => 'Test Event',
				'status'     => 'publish',
				'start_date' => '2020-01-01 12:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;
		// Enable the global stock on the Event.
		update_post_meta( $event_id, Global_Stock::GLOBAL_STOCK_ENABLED, 1 );
		// Set the Event global stock level to 30.
		update_post_meta( $event_id, Global_Stock::GLOBAL_STOCK_LEVEL, 30 );

		$ticket_a_id = $this->create_tc_ticket(
			$event_id,
			20,
			[
				'tribe-ticket' => [
					'mode'           => Global_Stock::CAPPED_STOCK_MODE,
					'event_capacity' => 30,
					'capacity'       => 20,
				],
			]
		);
		$ticket_b_id = $this->create_tc_ticket(
			$event_id,
			20,
			[
				'tribe-ticket' => [
					'mode'           => Global_Stock::GLOBAL_STOCK_MODE,
					'event_capacity' => 30,
					'capacity'       => 30,
				],
			]
		);

		// Get the ticket objects.
		$ticket_a = tribe( Module::class )->get_ticket( $event_id, $ticket_a_id );
		$ticket_b = tribe( Module::class )->get_ticket( $event_id, $ticket_b_id );

		// Make sure both tickets are valid Ticket Object.
		$this->assertInstanceOf( Ticket_Object::class, $ticket_a );
		$this->assertInstanceOf( Ticket_Object::class, $ticket_b );

		// Create order.
		$order = $this->create_order(
			[
				$ticket_a_id => 5,
				$ticket_b_id => 5,
			]
		);

		tribe_cache()->delete( 'tec_tickets_attendees_by_ticket_id' );

		// Refresh the ticket objects.
		$ticket_a = tribe( Module::class )->get_ticket( $event_id, $ticket_a_id );
		$ticket_b = tribe( Module::class )->get_ticket( $event_id, $ticket_b_id );

		$this->assertEquals( 20, $ticket_a->capacity() );
		$this->assertEquals( 20 - 5, $ticket_a->stock() );
		$this->assertEquals( 20 - 5, $ticket_a->available() );
		$this->assertEquals( 20 - 5, $ticket_a->inventory() );

		$this->assertEquals( 30, $ticket_b->capacity() );
		$this->assertEquals( 30 - 10, $ticket_b->stock() );
		$this->assertEquals( 30 - 10, $ticket_b->available() );
		$this->assertEquals( 30 - 10, $ticket_b->inventory() );

		$new_global_capacity = 40;
		// Update the Event's capacity manually.
		tribe_tickets_update_capacity( $event_id, $new_global_capacity );

		/** @var Tickets_Handler $handler */
		$handler = tribe( 'tickets.handler' );
		$handler->sync_shared_capacity( $event_id, $new_global_capacity );

		// Refresh the ticket objects.
		$ticket_a = tribe( Module::class )->get_ticket( $event_id, $ticket_a_id );
		$ticket_b = tribe( Module::class )->get_ticket( $event_id, $ticket_b_id );

		$this->assertEquals( 20, $ticket_a->capacity(), 'Capped Ticket Capacity should remain 20' );
		$this->assertEquals( 20, tribe_tickets_get_capacity( $ticket_a_id ), 'Capped Ticket Capacity should remain 20' );
		$this->assertEquals( 20 - 5, $ticket_a->stock(), 'Capped Ticket Stock should remain 15' );
		$this->assertEquals( 20 - 5, $ticket_a->available(), 'Capped Ticket available should remain 15' );
		$this->assertEquals( 20 - 5, $ticket_a->inventory(), 'Capped Ticket inventory should remain 15' );

		$this->assertEquals( 40, $ticket_b->capacity(), 'Global Ticket Capacity should be increased to 40' );
		$this->assertEquals( 40, tribe_tickets_get_capacity( $ticket_b_id ), 'Global Ticket Capacity should be increased to 40' );
		$this->assertEquals( 40 - 10, $ticket_b->stock(), 'Global Ticket Stock should be 30' );
		$this->assertEquals( 40 - 10, $ticket_b->available(), 'Global Ticket available should be 30' );
		$this->assertEquals( 40 - 10, $ticket_b->inventory(), 'Global Ticket inventory should be 30' );

		$event_ticket_counts    = \Tribe__Tickets__Tickets::get_ticket_counts( $event_id );
		$expected_ticket_counts = [
			'count'     => 2,
			'stock'     => 30,
			'global'    => 1,
			'unlimited' => 0,
			'available' => 30,
		];

		$this->assertEqualSets( $expected_ticket_counts, $event_ticket_counts['tickets'] );
	}

	public function test_tc_total_shared_capacity_decrease_should_decrease_capped_capacity() {
		$event_id = tribe_events()->set_args(
			[
				'title'      => 'Test Event',
				'status'     => 'publish',
				'start_date' => '2020-01-01 12:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;
		// Enable the global stock on the Event.
		update_post_meta( $event_id, Global_Stock::GLOBAL_STOCK_ENABLED, 1 );
		// Set the Event global stock level to 30.
		update_post_meta( $event_id, Global_Stock::GLOBAL_STOCK_LEVEL, 30 );

		$ticket_a_id = $this->create_tc_ticket(
			$event_id,
			20,
			[
				'tribe-ticket' => [
					'mode'     => Global_Stock::CAPPED_STOCK_MODE,
					'capacity' => 20,
				],
			]
		);
		$ticket_b_id = $this->create_tc_ticket(
			$event_id,
			20,
			[
				'tribe-ticket' => [
					'mode'     => Global_Stock::GLOBAL_STOCK_MODE,
					'capacity' => 30,
				],
			]
		);

		// Get the ticket objects.
		$ticket_a = tribe( Module::class )->get_ticket( $event_id, $ticket_a_id );
		$ticket_b = tribe( Module::class )->get_ticket( $event_id, $ticket_b_id );

		// Make sure both tickets are valid Ticket Object.
		$this->assertInstanceOf( Ticket_Object::class, $ticket_a );
		$this->assertInstanceOf( Ticket_Object::class, $ticket_b );

		// Create order for Global stock ticket.
		$order = $this->create_order(
			[
				$ticket_a_id => 5,
				$ticket_b_id => 5,
			]
		);

		$new_global_capacity = 15;

		/** @var Tickets_Handler $handler */
		$handler = tribe( 'tickets.handler' );
		$handler->sync_shared_capacity( $event_id, $new_global_capacity );

		// Update the Event's capacity manually.
		tribe_tickets_update_capacity( $event_id, $new_global_capacity );
		update_post_meta( $event_id, Global_Stock::GLOBAL_STOCK_LEVEL, $new_global_capacity - 10 );

		// Refresh the ticket objects.
		$ticket_a = tribe( Module::class )->get_ticket( $event_id, $ticket_a_id );
		$ticket_b = tribe( Module::class )->get_ticket( $event_id, $ticket_b_id );

		// Capped tickets capacity decreased to lowered global capacity, so this should behave as a global capacity ticket.
		$this->assertEquals( 15, $ticket_a->capacity(), 'Capped Ticket Capacity should be decreased to 15' );
		$this->assertEquals( 15, tribe_tickets_get_capacity( $ticket_a_id ), 'Capped Ticket Capacity should be decreased to 15' );
		$this->assertEquals( 15 - 10, $ticket_a->stock(), 'Capped Ticket Stock should be decreased to 5' );
		$this->assertEquals( 15 - 10, $ticket_a->available(), 'Capped Ticket available should be decreased to 5' );
		$this->assertEquals( 15 - 10, $ticket_a->inventory(), 'Capped Ticket inventory should be decreased to 5' );

		$this->assertEquals( 15, $ticket_b->capacity(), 'Global Ticket Capacity should be decreased to 15' );
		$this->assertEquals( 15, tribe_tickets_get_capacity( $ticket_b_id ), 'Global Ticket Capacity should be decreased to 15' );
		$this->assertEquals( 15 - 10, $ticket_b->stock(), 'Global Ticket Stock should be 5' );
		$this->assertEquals( 15 - 10, $ticket_b->available(), 'Global Ticket available should be 5' );
		$this->assertEquals( 15 - 10, $ticket_b->inventory(), 'Global Ticket inventory should be 5' );

		$event_ticket_counts    = \Tribe__Tickets__Tickets::get_ticket_counts( $event_id );
		$expected_ticket_counts = [
			'count'     => 2,
			'stock'     => 5,
			'global'    => 1,
			'unlimited' => 0,
			'available' => 5,
		];

		$this->assertEqualSets( $expected_ticket_counts, $event_ticket_counts['tickets'] );
	}

	public function test_tc_total_shared_capacity_should_not_change_after_unlimited_capacity_ticket_purchase() {
		$event_id = tribe_events()->set_args(
			[
				'title'      => 'Test Event',
				'status'     => 'publish',
				'start_date' => '2020-01-01 12:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;
		// Enable the global stock on the Event.
		update_post_meta( $event_id, Global_Stock::GLOBAL_STOCK_ENABLED, 1 );
		// Set the Event global stock level to 20.
		update_post_meta( $event_id, Global_Stock::GLOBAL_STOCK_LEVEL, 20 );

		$ticket_a_id = $this->create_tc_ticket(
			$event_id,
			20,
			[
				'tribe-ticket' => [
					'mode'     => Global_Stock::OWN_STOCK_MODE,
					'capacity' => -1,
				],
			]
		);
		$ticket_b_id = $this->create_tc_ticket(
			$event_id,
			20,
			[
				'tribe-ticket' => [
					'mode'     => Global_Stock::GLOBAL_STOCK_MODE,
					'capacity' => 20,
				],
			]
		);

		// Get the ticket objects.
		$ticket_a = tribe( Module::class )->get_ticket( $event_id, $ticket_a_id );
		$ticket_b = tribe( Module::class )->get_ticket( $event_id, $ticket_b_id );

		$global_stock = new Global_Stock( $event_id );

		$this->assertTrue( $global_stock->is_enabled(), 'Global stock should be enabled.' );
		$this->assertEquals( -1, tribe_get_event_capacity( $event_id ), 'Total Event capacity should be -1 or unlimited' );
		$this->assertEquals( 20, $global_stock->get_stock_level(), 'Global stock should be 20' );

		// Make sure both tickets are valid Ticket Object.
		$this->assertInstanceOf( Ticket_Object::class, $ticket_a );
		$this->assertInstanceOf( Ticket_Object::class, $ticket_b );

		$this->assertEquals( -1, $ticket_a->capacity() );
		$this->assertEquals( -1, $ticket_a->stock() );
		$this->assertEquals( -1, $ticket_a->available() );
		$this->assertEquals( -1, $ticket_a->inventory() );

		$this->assertEquals( 20, $ticket_b->capacity() );
		$this->assertEquals( 20, $ticket_b->stock() );
		$this->assertEquals( 20, $ticket_b->available() );
		$this->assertEquals( 20, $ticket_b->inventory() );

		// Create order for Unlimited capacity ticket only.
		$order = $this->create_order(
			[
				$ticket_a_id => 5,
			]
		);

		// Refresh the ticket objects.
		$ticket_a = tribe( Module::class )->get_ticket( $event_id, $ticket_a_id );
		$ticket_b = tribe( Module::class )->get_ticket( $event_id, $ticket_b_id );

		$this->assertEquals( -1, $ticket_a->capacity() );
		$this->assertEquals( -1, $ticket_a->stock() );
		$this->assertEquals( -1, $ticket_a->available() );
		$this->assertEquals( -1, $ticket_a->inventory() );

		// Shared capacity should not change.
		$this->assertEquals( 20, $ticket_b->capacity() );
		$this->assertEquals( 20, $ticket_b->stock() );
		$this->assertEquals( 20, $ticket_b->available() );
		$this->assertEquals( 20, $ticket_b->inventory() );

		$this->assertEquals( 20, $global_stock->get_stock_level(), 'Global stock should still be 20' );
	}
}
