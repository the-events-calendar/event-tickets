<?php

namespace Tribe\Tickets;

use Tribe\Events\Test\Factories\Event;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\Attendee_Maker as Attendee_Maker;
use Tribe__Tickets__Data_API as Data_API;
use Tribe__Tickets__RSVP as RSVP;
use Tribe__Tickets__Commerce__PayPal__Main as PayPal;
use Tribe__Tickets__Tickets_Handler as Handler;
use Tribe__Cache as Cache;

class CapacityTest extends \Codeception\TestCase\WPTestCase {

	use RSVP_Ticket_Maker;
	use PayPal_Ticket_Maker;
	use Attendee_Maker;

	public function setUp() {
		// before
		parent::setUp();

		// your set up methods here
		$this->factory()->event = new Event();
		$this->event_id         = $this->factory()->event->create();

		// Tribe__Tickets__Tickets_Handler handler for easier access
		$this->handler = new Handler;

		// Enable Tribe Commerce.
		add_filter( 'tribe_tickets_commerce_paypal_is_active', '__return_true' );
		add_filter( 'tribe_tickets_get_modules', function ( $modules ) {
			$modules['Tribe__Tickets__Commerce__PayPal__Main'] = tribe( 'tickets.commerce.paypal' )->plugin_name;

			return $modules;
		} );

		// Reset Data_API object so it sees Tribe Commerce.
		tribe_singleton( 'tickets.data_api', new Data_API );
	}

	public function tearDown() {
		// your tear down methods here
		unset( $this->event_id );

		// then
		parent::tearDown();
	}

	/**
	 * Invalidate (delete) the cached tickets from Tribe__Tickets__Tickets::get_all_event_tickets()
	 * which is called by many of these functions.
	 * Note this cached value currently has _no_ expiration - and is never unset/deleted!
	 *
	 * @param [type] $event_id
	 * @return void
	 */
	protected function invalidate_cache( $event_id ) {
		// why is this private?!?!
		$cache_key = 'tribe_event_tickets_from_' . $event_id;
		$cache     = new Cache();

		$cache->delete( $cache_key );
	}

	/**
	 * @test
	 * get_post_totals() should return the correct number of tickets initially
	 *
	 * @covers Tribe__Tickets__Tickets_Handler::get_post_totals()
	 */
	public function get_post_totals_should_return_the_correct_number_of_tickets_initially() {
		$num_tickets = 5;
		$capacity    = 5;
		$stock       = 3;
		$sales       = 2;

		// create 5 tickets
		$ticket_ids = $this->create_many_paypal_tickets(
			$num_tickets,
			$this->event_id,
			[
				'meta_input' => [
					'_capacity'   => $capacity,
					'_stock'      => $stock,
					'total_sales' => $sales,
				],
			]
		);

		$this->assertNotEmpty( $ticket_ids, 'Tickets not created!' );

		$test_data = $this->handler->get_post_totals( $this->event_id );

		$this->assertEquals( $num_tickets, $test_data['tickets'], 'Incorrect number of tickets.' );
	}

	/**
	 * @test
	 * get_post_totals() should return the correct number of pending tickets initially
	 *
	 * @covers Tribe__Tickets__Tickets_Handler::get_post_totals()
	 */
	public function get_post_totals_should_return_the_correct_number_of_pending_tickets_initially() {
		$num_tickets = 5;
		$capacity    = 5;
		$stock       = 3;
		$sales       = 2;

		// create 5 tickets
		$ticket_ids = $this->create_many_paypal_tickets(
			$num_tickets,
			$this->event_id,
			[
				'meta_input' => [
					'_capacity'   => $capacity,
					'_stock'      => $stock,
					'total_sales' => $sales,
				],
			]
		);

		$this->assertNotEmpty( $ticket_ids, 'Tickets not created!' );

		$test_data = $this->handler->get_post_totals( $this->event_id );

		$this->assertEquals( 0, $test_data['pending'], 'Incorrect total pending.' );
	}

	/**
	 * @test
	 * get_post_totals() should return the correct total capacity initially
	 *
	 * @covers Tribe__Tickets__Tickets_Handler::get_post_totals()
	 */
	public function get_post_totals_should_return_the_correct_total_capacity_initially() {
		$num_tickets = 5;
		$capacity    = 5;
		$stock       = 3;
		$sales       = 2;

		// create 5 tickets
		$ticket_ids = $this->create_many_paypal_tickets(
			$num_tickets,
			$this->event_id,
			[
				'meta_input' => [
					'_capacity'   => $capacity,
					'_stock'      => $stock,
					'total_sales' => $sales,
				],
			]
		);

		$this->assertNotEmpty( $ticket_ids, 'Tickets not created!' );

		$test_data = $this->handler->get_post_totals( $this->event_id );

		$this->assertEquals( ( $num_tickets * $capacity ), $test_data['capacity'], 'Incorrect total capacity.' );
	}

	/**
	 * @test
	 * get_post_totals() should return the correct total stock initially
	 *
	 * @covers Tribe__Tickets__Tickets_Handler::get_post_totals()
	 */
	public function get_post_totals_should_return_the_correct_total_stock_initially() {
		$num_tickets = 5;
		$capacity    = 5;
		$stock       = 3;
		$sales       = 2;

		// create 5 tickets
		$ticket_ids = $this->create_many_paypal_tickets(
			$num_tickets,
			$this->event_id,
			[
				'meta_input' => [
					'_capacity'   => $capacity,
					'_stock'      => $stock,
					'total_sales' => $sales,
				],
			]
		);

		$this->assertNotEmpty( $ticket_ids, 'Tickets not created!' );

		$test_data = $this->handler->get_post_totals( $this->event_id );

		$this->assertEquals( ( $num_tickets * $stock ), $test_data['stock'], 'Incorrect total stock.' );
	}

	/**
	 * @test
	 * get_post_totals() should return the correct total sales initially
	 *
	 * @covers Tribe__Tickets__Tickets_Handler::get_post_totals()
	 */
	public function get_post_totals_should_return_the_correct_total_sales_initially() {
		$num_tickets = 5;
		$capacity    = 5;
		$stock       = 3;
		$sales       = 2;

		// create 5 tickets
		$ticket_ids = $this->create_many_paypal_tickets(
			$num_tickets,
			$this->event_id,
			[
				'meta_input' => [
					'_capacity'   => $capacity,
					'_stock'      => $stock,
					'total_sales' => $sales,
				],
			]
		);

		$this->assertNotEmpty( $ticket_ids, 'Tickets not created!' );

		$test_data = $this->handler->get_post_totals( $this->event_id );

		$this->assertEquals( ( $num_tickets * $sales ), $test_data['sold'], 'Incorrect total sales.' );
	}

	/**
	 * @test
	 * get_post_totals() should detect unlimited tickets appropriately
	 *
	 * @covers Tribe__Tickets__Tickets_Handler::get_post_totals()
	 */
	public function get_post_totals_should_detect_unlimited_tickets_appropriately_initially() {
		$ticket_id = $this->create_paypal_ticket(
			$this->event_id,
			1,
			[
				'meta_input' => [
					'_capacity'     => - 1,
					'_manage_stock' => 'no',
					'total_sales'   => 2,
				],
			]
		);

		$this->invalidate_cache( $this->event_id );

		$test_data = $this->handler->get_post_totals( $this->event_id );

		// Test for existing unlimited ticket
		$this->assertTrue( $test_data['has_unlimited'], 'Incorrect nondetection of existing unlimited tickets.' );
	}

	/**
	 * @test
	 * get_post_totals() should return the correct number of tickets on change
	 *
	 * @covers Tribe__Tickets__Tickets_Handler::get_post_totals()
	 */
	public function get_post_totals_should_return_the_correct_number_of_tickets_on_change() {
		$num_tickets = 5;
		$capacity    = 5;
		$stock       = 3;
		$sales       = 2;

		// create 5 tickets
		$ticket_ids = $this->create_many_paypal_tickets(
			$num_tickets,
			$this->event_id,
			[
				'meta_input' => [
					'_capacity'   => $capacity,
					'_stock'      => $stock,
					'total_sales' => $sales,
				],
			]
		);

		$this->assertNotEmpty( $ticket_ids, 'Tickets not created!' );

		$test_data = $this->handler->get_post_totals( $this->event_id );

		// Add another ticket.
		$ticket_b_id = $this->create_paypal_ticket(
			$this->event_id,
			1,
			[
				'meta_input' => [
					'_capacity'     => - 1,
					'_manage_stock' => 'no',
					'total_sales'   => 2,
				],
			]
		);

		$this->invalidate_cache( $this->event_id );

		$test_data = $this->handler->get_post_totals( $this->event_id );

		$this->assertEquals( $num_tickets, $test_data['tickets'], 'Incorrect number of tickets.' );
	}

	/**
	 * @test
	 * get_post_totals() should return the correct number of pending tickets on change
	 *
	 * @covers Tribe__Tickets__Tickets_Handler::get_post_totals()
	 */
	public function get_post_totals_should_return_the_correct_number_of_pending_tickets_on_change() {
		$num_tickets = 5;
		$capacity    = 5;
		$stock       = 3;
		$sales       = 2;

		// create 5 tickets
		$ticket_ids = $this->create_many_paypal_tickets(
			$num_tickets,
			$this->event_id,
			[
				'meta_input' => [
					'_capacity'   => $capacity,
					'_stock'      => $stock,
					'total_sales' => $sales,
				],
			]
		);

		$this->assertNotEmpty( $ticket_ids, 'Tickets not created!' );

		$test_data = $this->handler->get_post_totals( $this->event_id );

		// Add another ticket.
		$ticket_b_id = $this->create_paypal_ticket(
			$this->event_id,
			1,
			[
				'meta_input' => [
					'_capacity'     => - 1,
					'_manage_stock' => 'no',
					'total_sales'   => 2,
				],
			]
		);

		$this->invalidate_cache( $this->event_id );

		$test_data = $this->handler->get_post_totals( $this->event_id );

		$this->assertEquals( 0, $test_data['pending'], 'Incorrect total pending.' );
	}

	/**
	 * @test
	 * get_post_totals() should return the correct total capacity on change
	 *
	 * @covers Tribe__Tickets__Tickets_Handler::get_post_totals()
	 */
	public function get_post_totals_should_return_the_correct_total_capacity_on_change() {
		$num_tickets = 5;
		$capacity    = 5;
		$stock       = 3;
		$sales       = 2;

		// create 5 tickets
		$ticket_ids = $this->create_many_paypal_tickets(
			$num_tickets,
			$this->event_id,
			[
				'meta_input' => [
					'_capacity'   => $capacity,
					'_stock'      => $stock,
					'total_sales' => $sales,
				],
			]
		);

		$this->assertNotEmpty( $ticket_ids, 'Tickets not created!' );

		$test_data = $this->handler->get_post_totals( $this->event_id );

		// Add another ticket.
		$ticket_b_id = $this->create_paypal_ticket(
			$this->event_id,
			1,
			[
				'meta_input' => [
					'_capacity'     => - 1,
					'_manage_stock' => 'no',
					'total_sales'   => 2,
				],
			]
		);

		$this->invalidate_cache( $this->event_id );

		$test_data = $this->handler->get_post_totals( $this->event_id );

		$this->assertEquals( ( $num_tickets * $capacity ), $test_data['capacity'], 'Incorrect total capacity.' );
	}

	/**
	 * @test
	 * get_post_totals() should return the correct total stock on change
	 *
	 * @covers Tribe__Tickets__Tickets_Handler::get_post_totals()
	 */
	public function get_post_totals_should_return_the_correct_total_stock_on_change() {
		$num_tickets = 5;
		$capacity    = 5;
		$stock       = 3;
		$sales       = 2;

		// create 5 tickets
		$ticket_ids = $this->create_many_paypal_tickets(
			$num_tickets,
			$this->event_id,
			[
				'meta_input' => [
					'_capacity'   => $capacity,
					'_stock'      => $stock,
					'total_sales' => $sales,
				],
			]
		);

		$this->assertNotEmpty( $ticket_ids, 'Tickets not created!' );

		$test_data = $this->handler->get_post_totals( $this->event_id );

		// Add another ticket.
		$ticket_b_id = $this->create_paypal_ticket(
			$this->event_id,
			1,
			[
				'meta_input' => [
					'_capacity'     => - 1,
					'_manage_stock' => 'no',
					'total_sales'   => 2,
				],
			]
		);

		$this->invalidate_cache( $this->event_id );

		$test_data = $this->handler->get_post_totals( $this->event_id );

		$this->assertEquals( ( $num_tickets * $stock ), $test_data['stock'], 'Incorrect total stock.' );
	}

	/**
	 * @test
	 * get_post_totals() should return the correct total sales on change
	 *
	 * @covers Tribe__Tickets__Tickets_Handler::get_post_totals()
	 */
	public function get_post_totals_should_return_the_correct_total_sales_on_change() {
		$num_tickets = 5;
		$capacity    = 5;
		$stock       = 3;
		$sales       = 2;

		// create 5 tickets
		$ticket_ids = $this->create_many_paypal_tickets(
			$num_tickets,
			$this->event_id,
			[
				'meta_input' => [
					'_capacity'   => $capacity,
					'_stock'      => $stock,
					'total_sales' => $sales,
				],
			]
		);

		$this->assertNotEmpty( $ticket_ids, 'Tickets not created!' );

		$test_data = $this->handler->get_post_totals( $this->event_id );

		// Add another ticket.
		$ticket_b_id = $this->create_paypal_ticket(
			$this->event_id,
			1,
			[
				'meta_input' => [
					'_capacity'     => - 1,
					'_manage_stock' => 'no',
					'total_sales'   => 2,
				],
			]
		);

		$this->invalidate_cache( $this->event_id );

		$test_data = $this->handler->get_post_totals( $this->event_id );

		$this->assertEquals( ( $num_tickets * $sales ), $test_data['sold'], 'Incorrect total sales.' );
	}

	/**
	 * @test
	 * get_post_totals() should detect unlimited tickets appropriately on change
	 *
	 * @covers Tribe__Tickets__Tickets_Handler::get_post_totals()
	 */
	public function get_post_totals_should_detect_unlimited_tickets_appropriately_on_change() {
		$ticket_id = $this->create_paypal_ticket(
			$this->event_id,
			1,
			[
				'meta_input' => [
					'_capacity'     => -1,
					'_manage_stock' => 'no',
					'total_sales'   => 2,
				],
			]
		);

		$this->invalidate_cache( $this->event_id );

		$test_data = $this->handler->get_post_totals( $this->event_id );

		// Test for existing unlimited ticket
		$this->assertFalse( $test_data['has_unlimited'], 'Incorrect nondetection of existing unlimited tickets.' );

		$ticket_b_id = $this->create_paypal_ticket(
			$this->event_id,
			1,
			[
				'meta_input' => [
					'_capacity'     => - 1,
					'_manage_stock' => 'no',
					'total_sales'   => 2,
				],
			]
		);

		$this->invalidate_cache( $this->event_id );

		$test_data = $this->handler->get_post_totals( $this->event_id );

		// Test for existing unlimited ticket
		$this->assertTrue( $test_data['has_unlimited'], 'Incorrect detection of existing unlimited tickets.' );
	}

}
