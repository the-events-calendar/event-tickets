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

	public function test_tc_shared_capacity_update_after_purchase() {
		$maker = new Event();
		$event_id = $maker->create();


		$overrides = [
			'tribe-ticket' => [
				'mode'           => \Tribe__Tickets__Global_Stock::CAPPED_STOCK_MODE,
				'event_capacity' => 20,
				'capacity'       => 10,
			],
		];

		$ticket_a_id   = $this->create_tc_ticket( $event_id, 10, $overrides );

		$overrides = [
			'tribe-ticket' => [
				'mode'           => \Tribe__Tickets__Global_Stock::GLOBAL_STOCK_MODE,
				'event_capacity' => 20,
				'capacity'       => 20,
			],
		];

		$ticket_b_id   = $this->create_tc_ticket( $event_id, 20, $overrides );

		// get the ticket objects.
		$ticket_a = tribe( Module::class )->get_ticket( $event_id, $ticket_a_id );
		$ticket_b = tribe( Module::class )->get_ticket( $event_id, $ticket_b_id );

		// Make sure both tickets are valid Ticket Object.
		$this->assertInstanceOf( \Tribe__Tickets__Ticket_Object::class, $ticket_a );
		$this->assertInstanceOf( \Tribe__Tickets__Ticket_Object::class, $ticket_b );

		// create order for Global stock ticket.
		$cart = new Cart();
		$cart->get_repository()->add_item( $ticket_b_id, 5 );

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

		// refresh the ticket objects.
		$ticket_b = tribe( Module::class )->get_ticket( $event_id, $ticket_b_id );

		codecept_debug( $ticket_b );
		$this->assertEquals( $new_global_capacity, $ticket_b->capacity(), 'Ticket Capacity should be 10' );
		$this->assertEquals( $new_global_capacity, tribe_tickets_get_capacity( $ticket_b_id ), 'Ticket Capacity should be 10' );
		$this->assertEquals( $new_global_capacity-5, $ticket_b->stock(), 'Ticket Stock should be 5' );
		$this->assertEquals( $new_global_capacity-5, $ticket_b->available(), 'Ticket available should be 5' );
		$this->assertEquals( $new_global_capacity-5, $ticket_b->inventory(), 'Ticket inventory should be 5' );
		$this->assertEquals( $new_global_capacity-5, $global_stock->get_stock_level(), 'Global stock level should be 5' );

	}
}