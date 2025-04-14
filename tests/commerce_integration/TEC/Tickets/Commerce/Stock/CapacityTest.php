<?php

namespace TEC\Tickets\Commerce\Stock;

use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Provider;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Gateways\PayPal\Gateway;
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Not_Completed;
use TEC\Tickets\Commerce\Status\Pending;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Events\Test\Factories\Event;

class CapacityTest extends \Codeception\TestCase\WPTestCase {

	use Ticket_Maker;

	public function test_if_provider_is_loaded() {
		$provider = tribe( Module::class );

		$this->assertNotFalse( $provider );
	}

	public function test_if_tickets_can_be_created_and_purchased() {
		$maker = new Event();
		$event_id = $maker->create();

		// create ticket with default capacity of 100.
		$ticket_a_id = $this->create_tc_ticket( $event_id, 10 );

		// get the ticket.
		$ticket = tribe( Module::class )->get_ticket( $event_id, $ticket_a_id );

		$this->assertEquals( 100, $ticket->capacity(), 'Ticket capacity should be 100' );
		$this->assertEquals( 100, $ticket->available(), 'Ticket availability should be 100' );

		// create order.
		$cart = new Cart();
		$cart->get_repository()->upsert_item( $ticket_a_id, 5 );

		$purchaser = [
			'purchaser_user_id'    => 0,
			'purchaser_full_name'  => 'Test Purchaser',
			'purchaser_first_name' => 'Test',
			'purchaser_last_name'  => 'Purchaser',
			'purchaser_email'      => 'test@test.com',
		];

		$order     = tribe( Order::class )->create_from_cart( tribe( Gateway::class ), $purchaser );
		$completed = tribe( Order::class )->modify_status( $order->ID, Pending::SLUG );

		// refresh ticket.
		$ticket = tribe( Module::class )->get_ticket( $event_id, $ticket_a_id );

		$this->assertEquals( 95, $ticket->available(), 'Ticket availability should be 95 after purchasing 5' );
	}

	/**
	 * Tests if the ticket's count is correct.
	 *
	 * @test
	 */
	public function check_if_tickets_count_correct() {
		$event_maker = new Event();
		$event_id = $event_maker->create();

		// Create ticket with default capacity of 100.
		$ticket_a_id = $this->create_tc_ticket($event_id, 10);

		// Get the ticket.
		$ticket = tribe(Module::class)->get_ticket($event_id, $ticket_a_id);

		$this->assertEquals(100, $ticket->capacity(), 'Ticket capacity should be 100.');
		$this->assertEquals(100, $ticket->available(), 'Ticket availability should be 100.');

		// Create order.
		$cart = new Cart();
		$cart->get_repository()->upsert_item($ticket_a_id, 5);

		$purchaser = [
			'purchaser_user_id' => 0,
			'purchaser_full_name' => 'Test Purchaser',
			'purchaser_first_name' => 'Test',
			'purchaser_last_name' => 'Purchaser',
			'purchaser_email' => 'test@test.com',
		];

		// Create an order simulating a failed order. A failed order is one where the order goes from Pending to Not Completed.

		$order = tribe(Order::class)->create_from_cart(tribe(Gateway::class), $purchaser);
		$pending = tribe(Order::class)->modify_status($order->ID, Pending::SLUG);

		$this->assertTrue($pending, 'The move to Pending should be successful.');

		$ticket = tribe(Module::class)->get_ticket($event_id, $ticket_a_id);
		$this->assertEquals(95, $ticket->available(), 'Ticket availability should be 95 after Pending.');

		$not_completed = tribe(Order::class)->modify_status($order->ID, Not_Completed::SLUG);
		$this->assertTrue($not_completed, 'The move to Not_Completed should be successful.');

		$ticket = tribe(Module::class)->get_ticket($event_id, $ticket_a_id);
		$this->assertEquals(100, $ticket->available(), 'Ticket availability should be 100 after the order is moved to not completed.');

		// Now that the original has failed, simulate a successful one to confirm the availability is correct.
		$order = tribe(Order::class)->create_from_cart(tribe(Gateway::class), $purchaser);

		$pending = tribe(Order::class)->modify_status($order->ID, Pending::SLUG);
		$this->assertTrue($pending, 'The move to Pending should be successful.');
		$ticket = tribe(Module::class)->get_ticket($event_id, $ticket_a_id);
		$this->assertEquals(95, $ticket->available(), 'Ticket availability should be 95 after Pending.');

		$completed = tribe(Order::class)->modify_status($order->ID, Completed::SLUG);
		$this->assertTrue($completed, 'The move to Completed should be successful.');
		$ticket = tribe(Module::class)->get_ticket($event_id, $ticket_a_id);
		$this->assertEquals(95, $ticket->available(), 'Ticket availability should be 95 after Completed.');

	}

	public function test_purchasing_over_capacity() {
		$maker = new Event();
		$event_id = $maker->create();

		// create ticket with default capacity of 100.
		$ticket_a_id = $this->create_tc_ticket( $event_id, 10 );

		// get the ticket.
		$ticket = tribe( Module::class )->get_ticket( $event_id, $ticket_a_id );

		$this->assertEquals( 100, $ticket->capacity(), 'Ticket capacity should be 100' );
		$this->assertEquals( 100, $ticket->available(), 'Ticket availability should be 100' );

		//order data.
		$data['tickets'] = [
			[ 'ticket_id' => $ticket_a_id, 'quantity' => 200, 'obj' => $ticket ]
		];

		// try creating order.
		$cart = new Cart();
		$should_be_errors = $cart->get_repository()->process( $data );

		$this->assertIsArray( $should_be_errors );

		/**
		 * @var \WP_Error $error_a
		 */
		$error_a = $should_be_errors[0];

		$this->assertWPError( $error_a );
		$this->assertEquals( 'ticket-capacity-not-available', $error_a->get_error_code() );
	}
}
