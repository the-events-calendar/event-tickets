<?php

namespace TEC\Tickets\Commerce\Stock\SharedCapacity;

use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Gateways\PayPal\Gateway;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Provider;
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Pending;
use Tribe\Events\Test\Factories\Event;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;

/**
 * Class SharedCapacityTest
 *
 * @package TEC\Tickets\Commerce\Stock\SharedCapacity
 */
class SharedCapacityTest extends \Codeception\TestCase\WPTestCase {
	use Ticket_Maker;
	use With_Uopz;

	protected function setUp() {
		parent::setUp();
		$this->set_fn_return( \Tribe__Tickets__Ticket_Object::class, 'is_ticket_cache_enabled' , false );
	}

	protected function place_order_for_cart( $cart ) {

		$purchaser = [
			'purchaser_user_id'    => 0,
			'purchaser_full_name'  => 'Test Purchaser',
			'purchaser_first_name' => 'Test',
			'purchaser_last_name'  => 'Purchaser',
			'purchaser_email'      => 'test@test.com',
		];

		$order     = tribe( Order::class )->create_from_cart( tribe( Gateway::class ), $purchaser );
		$pending   = tribe( Order::class )->modify_status( $order->ID, Pending::SLUG );
		$completed = tribe( Order::class )->modify_status( $order->ID, Completed::SLUG );

		$cart->clear_cart();

		sleep( 3 );

		return $order;
	}

	public function test_tc_shared_capacity_purchase() {

		$maker = new Event();
		$event_id = $maker->create();

		$overrides = [
			'tribe-ticket' => [
				'mode'           => \Tribe__Tickets__Global_Stock::CAPPED_STOCK_MODE,
				'event_capacity' => 50,
				'capacity'       => 30,
			],
		];

		$ticket_a_id   = $this->create_tc_ticket( $event_id, 10, $overrides );

		$overrides = [
			'tribe-ticket' => [
				'mode'           => \Tribe__Tickets__Global_Stock::GLOBAL_STOCK_MODE,
				'event_capacity' => 50,
				'capacity'       => 50,
			],
		];

		$ticket_b_id   = $this->create_tc_ticket( $event_id, 20, $overrides );

		// get the ticket objects.
		$ticket_a = tribe( Module::class )->get_ticket( $event_id, $ticket_a_id );
		$ticket_b = tribe( Module::class )->get_ticket( $event_id, $ticket_b_id );

		// Make sure both tickets are valid Ticket Object.
		$this->assertInstanceOf( \Tribe__Tickets__Ticket_Object::class, $ticket_a );
		$this->assertInstanceOf( \Tribe__Tickets__Ticket_Object::class, $ticket_b );

		$this->assertEquals( 30, $ticket_a->capacity() );
		$this->assertEquals( 30, $ticket_a->stock() );
		$this->assertEquals( 30, $ticket_a->available() );
		$this->assertEquals( 30, $ticket_a->inventory() );

		$this->assertEquals( 50, $ticket_b->capacity() );
		$this->assertEquals( 50, $ticket_b->stock() );
		$this->assertEquals( 50, $ticket_b->available() );
		$this->assertEquals( 50, $ticket_b->inventory() );

		$global_stock = new \Tribe__Tickets__Global_Stock( $event_id );

		$this->assertTrue( $global_stock->is_enabled(), 'Global stock should be enabled.' );
		$this->assertEquals( 50, tribe_get_event_capacity( $event_id ), 'Total Event capacity should be 50' );
		$this->assertEquals( 50, $global_stock->get_stock_level(), 'Global stock should be 50' );

		// create order.
		$cart = new Cart();
		$cart->get_repository()->add_item( $ticket_a_id, 5 );
		$cart->get_repository()->add_item( $ticket_b_id, 5 );
		$this->place_order_for_cart( $cart );

		// refresh the ticket objects.
		$ticket_a = tribe( Module::class )->get_ticket( $event_id, $ticket_a_id );
		$ticket_b = tribe( Module::class )->get_ticket( $event_id, $ticket_b_id );

		$this->assertEquals( 30, $ticket_a->capacity() );
		$this->assertEquals( 30-5, $ticket_a->stock() );
		$this->assertEquals( 30-5, $ticket_a->available() );
		$this->assertEquals( 30-5, $ticket_a->inventory() );

		$this->assertEquals( 50, $ticket_b->capacity() );
		$this->assertEquals( 50-10, $ticket_b->stock() );
		$this->assertEquals( 50-10, $ticket_b->available() );
		$this->assertEquals( 50-10, $ticket_b->inventory() );

		$this->assertEquals( 50-10, $global_stock->get_stock_level(), 'Global stock should be 50-10 = 40' );

	}

	public function test_tc_total_shared_capacity_decreased_after_purchase() {
		$maker = new Event();
		$event_id = $maker->create();

		$overrides = [
			'tribe-ticket' => [
				'mode'           => \Tribe__Tickets__Global_Stock::GLOBAL_STOCK_MODE,
				'event_capacity' => 20,
				'capacity'       => 20,
			],
		];

		$ticket_b_id   = $this->create_tc_ticket( $event_id, 20, $overrides );

		// get the ticket object.
		$ticket_b = tribe( Module::class )->get_ticket( $event_id, $ticket_b_id );

		// Make sure it's a valid Ticket Object.
		$this->assertInstanceOf( \Tribe__Tickets__Ticket_Object::class, $ticket_b );

		// create order for Global stock ticket.
		$cart = new Cart();
		$cart->get_repository()->add_item( $ticket_b_id, 5 );
		$order = $this->place_order_for_cart( $cart );

		$this->assertEquals( 'tec-tc-completed', tec_tc_get_order( $order->ID )->post_status, 'Order should be in completed status.' );

		// refresh the ticket objects.
		$ticket_b = tribe( Module::class )->get_ticket( $event_id, $ticket_b_id );
		$global_stock = new \Tribe__Tickets__Global_Stock( $event_id );

		$this->assertEquals( 20, $ticket_b->capacity() );
		$this->assertEquals( 20-5, $ticket_b->stock() );
		$this->assertEquals( 20-5, $ticket_b->available() );
		$this->assertEquals( 20-5, $ticket_b->inventory() );
		$this->assertEquals( 20-5, $global_stock->get_stock_level() );

		$new_global_capacity = 10;
		/** @var \Tribe__Tickets__Tickets_Handler $handler */
		$handler = tribe( 'tickets.handler' );
		$handler->sync_shared_capacity( $event_id, $new_global_capacity );
		// update the Event's capacity manually.
		tribe_tickets_update_capacity( $event_id, $new_global_capacity );

		// refresh the ticket objects.
		$ticket_b = tribe( Module::class )->get_ticket( $event_id, $ticket_b_id );

		$this->assertEquals( $new_global_capacity, $ticket_b->capacity(), 'Ticket Capacity should be 10' );
		$this->assertEquals( $new_global_capacity, tribe_tickets_get_capacity( $ticket_b_id ), 'Ticket Capacity should be 10' );
		$this->assertEquals( $new_global_capacity-5, $ticket_b->stock(), 'Ticket Stock should be 5' );
		$this->assertEquals( $new_global_capacity-5, $ticket_b->available(), 'Ticket available should be 5' );
		$this->assertEquals( $new_global_capacity-5, $ticket_b->inventory(), 'Ticket inventory should be 5' );
		$this->assertEquals( $new_global_capacity-5, $global_stock->get_stock_level(), 'Global stock level should be 5' );

	}

	public function test_tc_total_shared_capacity_increased_after_purchase() {
		$maker = new Event();
		$event_id = $maker->create();

		$overrides = [
			'tribe-ticket' => [
				'mode'           => \Tribe__Tickets__Global_Stock::GLOBAL_STOCK_MODE,
				'event_capacity' => 20,
				'capacity'       => 20,
			],
		];

		$ticket_b_id   = $this->create_tc_ticket( $event_id, 20, $overrides );

		// get the ticket objects.
		$ticket_b = tribe( Module::class )->get_ticket( $event_id, $ticket_b_id );

		// Make sure both tickets are valid Ticket Object.
		$this->assertInstanceOf( \Tribe__Tickets__Ticket_Object::class, $ticket_b );

		// create order for Global stock ticket.
		$cart = new Cart();
		$cart->get_repository()->add_item( $ticket_b_id, 5 );

		$order = $this->place_order_for_cart( $cart );
		$this->assertEquals( 'tec-tc-completed', tec_tc_get_order( $order->ID )->post_status, 'Order should be in completed status.' );

		// refresh the ticket objects.
		$ticket_b = tribe( Module::class )->get_ticket( $event_id, $ticket_b_id );

		$this->assertEquals( 20, $ticket_b->capacity() );
		$this->assertEquals( 20-5, $ticket_b->stock() );
		$this->assertEquals( 20-5, $ticket_b->available() );
		$this->assertEquals( 20-5, $ticket_b->inventory() );

		$new_global_capacity = 30;

		/** @var \Tribe__Tickets__Tickets_Handler $handler */
		$handler = tribe( 'tickets.handler' );
		$handler->sync_shared_capacity( $event_id, $new_global_capacity );
		// update the Event's capacity manually.
		tribe_tickets_update_capacity( $event_id, $new_global_capacity );

		// refresh the ticket objects.
		$ticket_b = tribe( Module::class )->get_ticket( $event_id, $ticket_b_id );

		$this->assertEquals( $new_global_capacity, $ticket_b->capacity(), 'Ticket Capacity should be 30' );
		$this->assertEquals( $new_global_capacity, tribe_tickets_get_capacity( $ticket_b_id ), 'Ticket Capacity should be 30' );
		$this->assertEquals( $new_global_capacity-5, $ticket_b->stock(), 'Ticket Stock should be 25' );
		$this->assertEquals( $new_global_capacity-5, $ticket_b->available(), 'Ticket available should be 25' );
		$this->assertEquals( $new_global_capacity-5, $ticket_b->inventory(), 'Ticket inventory should be 25' );
	}

	public function test_tc_total_shared_capacity_increase_should_not_impact_capped_capacity() {
		$maker = new Event();
		$event_id = $maker->create();

		$overrides_a = [
			'tribe-ticket' => [
				'mode'           => \Tribe__Tickets__Global_Stock::CAPPED_STOCK_MODE,
				'event_capacity' => 30,
				'capacity'       => 20,
			],
		];

		$overrides_b = [
			'tribe-ticket' => [
				'mode'           => \Tribe__Tickets__Global_Stock::GLOBAL_STOCK_MODE,
				'event_capacity' => 30,
				'capacity'       => 30,
			],
		];

		$ticket_a_id   = $this->create_tc_ticket( $event_id, 20, $overrides_a );
		$ticket_b_id   = $this->create_tc_ticket( $event_id, 20, $overrides_b );

		// get the ticket objects.
		$ticket_a = tribe( Module::class )->get_ticket( $event_id, $ticket_a_id );
		$ticket_b = tribe( Module::class )->get_ticket( $event_id, $ticket_b_id );

		// Make sure both tickets are valid Ticket Object.
		$this->assertInstanceOf( \Tribe__Tickets__Ticket_Object::class, $ticket_a );
		$this->assertInstanceOf( \Tribe__Tickets__Ticket_Object::class, $ticket_b );

		// create order for Global stock ticket.
		$cart = new Cart();
		$cart->get_repository()->add_item( $ticket_a_id, 5 );
		$cart->get_repository()->add_item( $ticket_b_id, 5 );
		$order = $this->place_order_for_cart( $cart );
		$this->assertEquals( 'tec-tc-completed', tec_tc_get_order( $order->ID )->post_status, 'Order should be in completed status.' );

		// refresh the ticket objects.
		$ticket_a = tribe( Module::class )->get_ticket( $event_id, $ticket_a_id );
		$ticket_b = tribe( Module::class )->get_ticket( $event_id, $ticket_b_id );

		$this->assertEquals( 20, $ticket_a->capacity() );
		$this->assertEquals( 20-5, $ticket_a->stock() );
		$this->assertEquals( 20-5, $ticket_a->available() );
		$this->assertEquals( 20-5, $ticket_a->inventory() );

		$this->assertEquals( 30, $ticket_b->capacity() );
		$this->assertEquals( 30-10, $ticket_b->stock() );
		$this->assertEquals( 30-10, $ticket_b->available() );
		$this->assertEquals( 30-10, $ticket_b->inventory() );

		$new_global_capacity = 40;

		/** @var \Tribe__Tickets__Tickets_Handler $handler */
		$handler = tribe( 'tickets.handler' );
		$handler->sync_shared_capacity( $event_id, $new_global_capacity );

		// update the Event's capacity manually.
		tribe_tickets_update_capacity( $event_id, $new_global_capacity );

		$global_stock = new \Tribe__Tickets__Global_Stock( $event_id );
		$global_stock_level = $global_stock->get_stock_level();

		// refresh the ticket objects.
		$ticket_a = tribe( Module::class )->get_ticket( $event_id, $ticket_a_id );
		$ticket_b = tribe( Module::class )->get_ticket( $event_id, $ticket_b_id );

		$this->assertEquals( 20, $ticket_a->capacity(), 'Capped Ticket Capacity should remain 20' );
		$this->assertEquals( 20, tribe_tickets_get_capacity( $ticket_a_id ), 'Capped Ticket Capacity should remain 20' );
		$this->assertEquals( 20-5, $ticket_a->stock(), 'Capped Ticket Stock should remain 15' );
		$this->assertEquals( 20-5, $ticket_a->available(), 'Capped Ticket available should remain 15' );
		$this->assertEquals( 20-5, $ticket_a->inventory(), 'Capped Ticket inventory should remain 15' );

		$this->assertEquals( $new_global_capacity, $ticket_b->capacity(), 'Global Ticket Capacity should be increased to 40' );
		$this->assertEquals( $new_global_capacity, tribe_tickets_get_capacity( $ticket_b_id ), 'Global Ticket Capacity should be increased to 40' );
		$this->assertEquals( $global_stock_level, $ticket_b->stock(), 'Global Ticket Stock should be 30' );
		$this->assertEquals( $global_stock_level, $ticket_b->available(), 'Global Ticket available should be 30' );
		$this->assertEquals( $global_stock_level, $ticket_b->inventory(), 'Global Ticket inventory should be 30' );

		$event_ticket_counts = \Tribe__Tickets__Tickets::get_ticket_counts( $event_id );
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
		$maker = new Event();
		$event_id = $maker->create();

		$overrides_a = [
			'tribe-ticket' => [
				'mode'           => \Tribe__Tickets__Global_Stock::CAPPED_STOCK_MODE,
				'event_capacity' => 30,
				'capacity'       => 20,
			],
		];

		$overrides_b = [
			'tribe-ticket' => [
				'mode'           => \Tribe__Tickets__Global_Stock::GLOBAL_STOCK_MODE,
				'event_capacity' => 30,
				'capacity'       => 30,
			],
		];

		$ticket_a_id   = $this->create_tc_ticket( $event_id, 20, $overrides_a );
		$ticket_b_id   = $this->create_tc_ticket( $event_id, 20, $overrides_b );

		// get the ticket objects.
		$ticket_a = tribe( Module::class )->get_ticket( $event_id, $ticket_a_id );
		$ticket_b = tribe( Module::class )->get_ticket( $event_id, $ticket_b_id );

		// Make sure both tickets are valid Ticket Object.
		$this->assertInstanceOf( \Tribe__Tickets__Ticket_Object::class, $ticket_a );
		$this->assertInstanceOf( \Tribe__Tickets__Ticket_Object::class, $ticket_b );

		// create order for Global stock ticket.
		$cart = new Cart();
		$cart->get_repository()->add_item( $ticket_a_id, 5 );
		$cart->get_repository()->add_item( $ticket_b_id, 5 );
		$order = $this->place_order_for_cart( $cart );
		$this->assertEquals( 'tec-tc-completed', tec_tc_get_order( $order->ID )->post_status, 'Order should be in completed status.' );

		$new_global_capacity = 15;

		/** @var \Tribe__Tickets__Tickets_Handler $handler */
		$handler = tribe( 'tickets.handler' );
		$handler->sync_shared_capacity( $event_id, $new_global_capacity );

		// update the Event's capacity manually.
		tribe_tickets_update_capacity( $event_id, $new_global_capacity );

		$global_stock = new \Tribe__Tickets__Global_Stock( $event_id );
		$global_stock_level = $global_stock->get_stock_level();

		// refresh the ticket objects.
		$ticket_a = tribe( Module::class )->get_ticket( $event_id, $ticket_a_id );
		$ticket_b = tribe( Module::class )->get_ticket( $event_id, $ticket_b_id );

		// capped tickets capacity decreased to lowered global capacity, so this should behave as a global capacity ticket.
		$this->assertEquals( 15, $ticket_a->capacity(), 'Capped Ticket Capacity should be decreased to 15' );
		$this->assertEquals( 15, tribe_tickets_get_capacity( $ticket_a_id ), 'Capped Ticket Capacity should be decreased to 15' );
		$this->assertEquals( 15-10, $ticket_a->stock(), 'Capped Ticket Stock should be decreased to 5' );
		$this->assertEquals( 15-10, $ticket_a->available(), 'Capped Ticket available should be decreased to 5' );
		$this->assertEquals( 15-10, $ticket_a->inventory(), 'Capped Ticket inventory should be decreased to 5' );

		$this->assertEquals( $new_global_capacity, $ticket_b->capacity(), 'Global Ticket Capacity should be decreased to 15' );
		$this->assertEquals( $new_global_capacity, tribe_tickets_get_capacity( $ticket_b_id ), 'Global Ticket Capacity should be decreased to 15' );
		$this->assertEquals( $global_stock_level, $ticket_b->stock(), 'Global Ticket Stock should be 5' );
		$this->assertEquals( $global_stock_level, $ticket_b->available(), 'Global Ticket available should be 5' );
		$this->assertEquals( $global_stock_level, $ticket_b->inventory(), 'Global Ticket inventory should be 5' );

		$event_ticket_counts = \Tribe__Tickets__Tickets::get_ticket_counts( $event_id );
		$expected_ticket_counts = [
			'count'     => 2,
			'stock'     => 5,
			'global'    => 1,
			'unlimited' => 0,
			'available' => 5,
		];

		$this->assertEqualSets( $expected_ticket_counts, $event_ticket_counts['tickets'] );
	}
}