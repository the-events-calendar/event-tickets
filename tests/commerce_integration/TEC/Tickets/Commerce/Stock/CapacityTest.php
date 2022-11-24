<?php

namespace TEC\Tickets\Commerce\Stock;

use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Provider;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Gateways\PayPal\Gateway;
use TEC\Tickets\Commerce\Status\Pending;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Events\Test\Factories\Event;

class CapacityTest extends \Codeception\TestCase\WPTestCase {

	use Ticket_Maker;

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
		$cart->get_repository()->add_item( $ticket_a_id, 5 );

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