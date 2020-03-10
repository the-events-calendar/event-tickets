<?php

namespace Tribe\Tickets;

use Tribe\Events\Test\Factories\Event;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe__Tickets__Data_API as Data_API;
use Tribe__Tickets__Tickets_Handler as Handler;
use Tribe__Tickets__Tickets as Tickets;
use Tribe__Tickets__RSVP as RSVP;
use Tribe__Tickets__Global_Stock as Global_Stock;

class GetPostTotalsTest extends \Codeception\TestCase\WPTestCase {

	use PayPal_Ticket_Maker;
	use RSVP_Ticket_Maker;

	public function setUp() {
		// before
		parent::setUp();

		// your set up methods here
		$this->factory()->event = new Event();
		$this->event_id         = $this->factory()->event->create();

		// Set up some reused vars.
		$this->num_tickets = 5;
		$this->capacity    = 5;
		$this->stock       = 3;
		$this->sales       = 2;

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
		// refresh the event ID for each test.
		unset( $this->event_id );

		// then
		parent::tearDown();
	}

	/**
	 * @test
	 * get_post_totals() should return the correct number of tickets initially
	 *
	 * @covers Tribe__Tickets__Tickets_Handler::get_post_totals()
	 */
	public function get_post_totals_should_return_the_correct_number_of_tickets_initially() {
		// create 5 tickets
		$ticket_ids = $this->create_many_paypal_tickets_basic(
			$this->num_tickets,
			$this->event_id,
			[
				'meta_input' => [
					tribe( 'tickets.handler' )->key_capacity => $this->capacity,
					'_stock'                                 => $this->stock,
					'total_sales'                            => $this->sales,
				],
			]
		);

		$this->assertNotEmpty( $ticket_ids, 'Tickets not created! ' . __METHOD__ );

		$test_data = $this->handler->get_post_totals( $this->event_id );

		$this->assertEquals( $this->num_tickets, $test_data['tickets'], 'Incorrect number of tickets.' );
	}

	/**
	 * @test
	 * get_post_totals() should return the correct number of pending tickets initially
	 *
	 * @covers Tribe__Tickets__Tickets_Handler::get_post_totals()
	 */
	public function get_post_totals_should_return_the_correct_number_of_pending_tickets_initially() {
		// create 5 tickets
		$ticket_ids = $this->create_many_paypal_tickets_basic(
			$this->num_tickets,
			$this->event_id,
			[
				'meta_input' => [
					tribe( 'tickets.handler' )->key_capacity => $this->capacity,
					'_stock'                                 => $this->stock,
					'total_sales'                            => $this->sales,
				],
			]
		);

		$this->assertNotEmpty( $ticket_ids, 'Tickets not created! ' . __METHOD__ );

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
		// create 5 tickets
		$ticket_ids = $this->create_many_paypal_tickets_basic(
			$this->num_tickets,
			$this->event_id,
			[
				'meta_input' => [
					tribe( 'tickets.handler' )->key_capacity => $this->capacity,
					'_stock'                                 => $this->stock,
					'total_sales'                            => $this->sales,
				],
			]
		);

		$this->assertNotEmpty( $ticket_ids, 'Tickets not created! ' . __METHOD__ );

		$test_data = $this->handler->get_post_totals( $this->event_id );

		$this->assertEquals( ( $this->num_tickets * $this->capacity ), $test_data['capacity'], 'Incorrect total capacity.' );
	}

	/**
	 * @test
	 * get_post_totals() should return the correct total stock initially
	 *
	 * @covers Tribe__Tickets__Tickets_Handler::get_post_totals()
	 */
	public function get_post_totals_should_return_the_correct_total_stock_initially() {
		// create 5 tickets
		$ticket_ids = $this->create_many_paypal_tickets_basic(
			$this->num_tickets,
			$this->event_id,
			[
				'meta_input' => [
					'_capacity'   => $this->capacity,
					'_stock'      => $this->stock,
					'total_sales' => $this->sales,
				],
			]
		);

		$this->assertNotEmpty( $ticket_ids, 'Tickets not created! ' . __METHOD__ );

		$test_data = $this->handler->get_post_totals( $this->event_id );

		$this->assertEquals( ( $this->num_tickets * $this->stock ), $test_data['stock'], 'Incorrect total stock.' );
	}

	/**
	 * @test
	 * get_post_totals() should return the correct total sales initially
	 *
	 * @covers Tribe__Tickets__Tickets_Handler::get_post_totals()
	 */
	public function get_post_totals_should_return_the_correct_total_sales_initially() {
		// create 5 tickets
		$ticket_ids = $this->create_many_paypal_tickets_basic(
			$this->num_tickets,
			$this->event_id,
			[
				'meta_input' => [
					'_capacity'   => $this->capacity,
					'_stock'      => $this->stock,
					'total_sales' => $this->sales,
				],
			]
		);

		$this->assertNotEmpty( $ticket_ids, 'Tickets not created! ' . __METHOD__ );

		$test_data = $this->handler->get_post_totals( $this->event_id );

		$this->assertEquals( ( $this->num_tickets * $this->sales ), $test_data['sold'], 'Incorrect total sales.' );
	}

	/**
	 * @test
	 * get_post_totals() should detect unlimited tickets appropriately
	 *
	 * @covers Tribe__Tickets__Tickets_Handler::get_post_totals()
	 */
	public function get_post_totals_should_detect_unlimited_tickets_appropriately_initially() {
		$ticket_id = $this->create_paypal_ticket_basic(
			$this->event_id,
			1,
			[
				'meta_input' => [
					tribe( 'tickets.handler' )->key_capacity => -1,
					'total_sales'                            => 2,
				],
			]
		);

		$test_data = $this->handler->get_post_totals( $this->event_id );

		// Test for existing unlimited ticket
		$this->assertTrue( $test_data['has_unlimited'], 'Did not detect existing unlimited ticket.' );
		$this->assertEquals( -1, $test_data['capacity'], 'Incorrect total capacity on unlimited tickets.' );
	}

	/**
	 * @test
	 * get_post_totals() should not detect unlimited tickets if there are none
	 *
	 * @covers Tribe__Tickets__Tickets_Handler::get_post_totals()
	 */
	public function get_post_totals_should_not_detect_unlimited_tickets_if_there_are_none() {
		// create 5 tickets
		$ticket_ids = $this->create_many_paypal_tickets_basic(
			$this->num_tickets,
			$this->event_id,
			[
				'meta_input' => [
					tribe( 'tickets.handler' )->key_capacity => $this->capacity,
					'_stock'                                 => $this->stock,
					'total_sales'                            => $this->sales,
				],
			]
		);

		$test_data = $this->handler->get_post_totals( $this->event_id );

		// Test for existing unlimited ticket
		$this->assertFalse( $test_data['has_unlimited'], 'Incorrect detection of nonexisting unlimited tickets.' );
	}

	/* Add Ticket Tests */

	/**
	 * @test
	 * get_post_totals() should return the correct number of tickets after adding a ticket
	 *
	 * @covers Tribe__Tickets__Tickets_Handler::get_post_totals()
	 */
	public function get_post_totals_should_return_the_correct_number_of_tickets_on_add() {
		// create 5 tickets
		$ticket_ids = $this->create_many_paypal_tickets_basic(
			$this->num_tickets,
			$this->event_id,
			[
				'meta_input' => [
					tribe( 'tickets.handler' )->key_capacity => $this->capacity,
					'_stock'                                 => $this->stock,
					'total_sales'                            => $this->sales,
				],
			]
		);

		$this->assertNotEmpty( $ticket_ids, 'Tickets not created! ' . __METHOD__ );

		$test_data = $this->handler->get_post_totals( $this->event_id );

		$this->assertEquals( $this->num_tickets, $test_data['tickets'], 'Incorrect number of tickets.' );

		// Add another ticket.
		$ticket_b_id = $this->create_paypal_ticket_basic(
			$this->event_id,
			1,
			[
				'meta_input' => [
					tribe( 'tickets.handler' )->key_capacity => $this->capacity,
					'_stock'                                 => $this->stock,
					'total_sales'                            => $this->sales,
				],
			]
		);

		$test_data = $this->handler->get_post_totals( $this->event_id );

		$this->assertEquals( $this->num_tickets + 1, $test_data['tickets'], 'Incorrect number of tickets.' );
	}

	/**
	 * @test
	 * get_post_totals() should return the correct number of pending tickets after adding a ticket
	 *
	 * @covers Tribe__Tickets__Tickets_Handler::get_post_totals()
	 */
	public function get_post_totals_should_return_the_correct_number_of_pending_tickets_on_add() {
		// create 5 tickets
		$ticket_ids = $this->create_many_paypal_tickets_basic(
			$this->num_tickets,
			$this->event_id,
			[
				'meta_input' => [
					tribe( 'tickets.handler' )->key_capacity => $this->capacity,
					'_stock'                                 => $this->stock,
					'total_sales'                            => $this->sales,
				],
			]
		);

		$this->assertNotEmpty( $ticket_ids, 'Tickets not created! ' . __METHOD__ );

		$test_data = $this->handler->get_post_totals( $this->event_id );

		// Add another ticket.
		$ticket_b_id = $this->create_paypal_ticket_basic(
			$this->event_id,
			1,
			[
				'meta_input' => [
					tribe( 'tickets.handler' )->key_capacity => -1,
					'total_sales'                            => 2,
				],
			]
		);

		$test_data = $this->handler->get_post_totals( $this->event_id );

		$this->assertEquals( 0, $test_data['pending'], 'Incorrect total pending.' );
	}

	/**
	 * @test
	 * get_post_totals() should return the correct total capacity after adding a ticket
	 *
	 * @covers Tribe__Tickets__Tickets_Handler::get_post_totals()
	 */
	public function get_post_totals_should_return_the_correct_total_capacity_on_add() {
		// create 5 tickets
		$ticket_ids = $this->create_many_paypal_tickets_basic(
			$this->num_tickets,
			$this->event_id,
			[
				'meta_input' => [
					tribe( 'tickets.handler' )->key_capacity => $this->capacity,
					'_stock'                                 => $this->stock,
					'total_sales'                            => $this->sales,
				],
			]
		);

		$this->assertNotEmpty( $ticket_ids, 'Tickets not created! ' . __METHOD__ );

		$test_data = $this->handler->get_post_totals( $this->event_id );

		// Add another ticket.
		$ticket_b_id = $this->create_paypal_ticket_basic(
			$this->event_id,
			1,
			[
				'meta_input' => [
					tribe( 'tickets.handler' )->key_capacity => $this->capacity,
					'_stock'                                 => $this->stock,
					'total_sales'                            => $this->sales,
				],
			]
		);

		$test_data = $this->handler->get_post_totals( $this->event_id );

		$this->assertEquals( ( ( $this->num_tickets + 1 ) * $this->capacity ), $test_data['capacity'], 'Incorrect total capacity.' );
	}

	/**
	 * @test
	 * get_post_totals() should return the correct total stock after adding a ticket
	 *
	 * @covers Tribe__Tickets__Tickets_Handler::get_post_totals()
	 */
	public function get_post_totals_should_return_the_correct_total_stock_on_add() {
		// create 5 tickets
		$ticket_ids = $this->create_many_paypal_tickets_basic(
			$this->num_tickets,
			$this->event_id,
			[
				'meta_input' => [
					tribe( 'tickets.handler' )->key_capacity => $this->capacity,
					'_stock'                                 => $this->stock,
					'total_sales'                            => $this->sales,
				],
			]
		);

		$this->assertNotEmpty( $ticket_ids, 'Tickets not created! ' . __METHOD__ );

		$test_data = $this->handler->get_post_totals( $this->event_id );

		// Add another ticket.
		$ticket_b_id = $this->create_paypal_ticket_basic(
			$this->event_id,
			1,
			[
				'meta_input' => [
					'_capacity'     => $this->capacity,
					'total_sales'   => $this->sales,
				],
			]
		);

		$test_data = $this->handler->get_post_totals( $this->event_id );

		$this->assertEquals( ( ( $this->num_tickets + 1)  * $this->stock ), $test_data['stock'], 'Incorrect total stock.' );
	}

	/**
	 * @test
	 * get_post_totals() should return the correct total sales after adding a ticket
	 *
	 * @covers Tribe__Tickets__Tickets_Handler::get_post_totals()
	 */
	public function get_post_totals_should_return_the_correct_total_sales_on_add() {
		// create 5 tickets
		$ticket_ids = $this->create_many_paypal_tickets_basic(
			$this->num_tickets,
			$this->event_id,
			[
				'meta_input' => [
					'_capacity'   => $this->capacity,
					'_stock'      => $this->stock,
					'total_sales' => $this->sales,
				],
			]
		);

		$this->assertNotEmpty( $ticket_ids, 'Tickets not created! ' . __METHOD__ );

		// Add another ticket.
		$ticket_b_id = $this->create_paypal_ticket_basic(
			$this->event_id,
			1,
			[
				'meta_input' => [
					'_capacity'     => -1,
					'total_sales'   => 2,
				],
			]
		);

		$test_data = $this->handler->get_post_totals( $this->event_id );

		$this->assertEquals( ( $this->num_tickets * $this->sales ) + 2, $test_data['sold'], 'Incorrect total sales.' );
	}

	/**
	 * @test
	 * get_post_totals() should detect unlimited tickets appropriately after adding a ticket
	 *
	 * @covers Tribe__Tickets__Tickets_Handler::get_post_totals()
	 */
	public function get_post_totals_should_detect_unlimited_tickets_appropriately_on_add() {
		$ticket_id = $this->create_paypal_ticket_basic(
			$this->event_id,
			1,
			[
				'meta_input' => [
					'_capacity'     => 3,
					'total_sales'   => 2,
				],
			]
		);

		$test_data = $this->handler->get_post_totals( $this->event_id );

		$this->assertFalse( $test_data['has_unlimited'], 'Incorrect detection of nonexisting unlimited tickets.' );

		$ticket_id_b = $this->create_paypal_ticket_basic(
			$this->event_id,
			1,
			[
				'meta_input' => [
					'_capacity'   => -1,
					'total_sales' => 2,
				],
			]
		);

		$tickets = Tickets::get_all_event_tickets( $this->event_id );

		$test_data = $this->handler->get_post_totals( $this->event_id );

		// Test for existing unlimited ticket
		$this->assertTrue( $test_data['has_unlimited'], 'Did not detect existing unlimited tickets.' );
		$this->assertEquals( -1, $test_data['capacity'], 'Incorrect total capacity on unlimited tickets.' );
	}

	/* Delete Ticket Tests */

	/**
	 * @test
	 * get_post_totals() should return the correct number of tickets after deleting a ticket
	 *
	 * @covers Tribe__Tickets__Tickets_Handler::get_post_totals()
	 */
	public function get_post_totals_should_return_the_correct_number_of_tickets_on_delete() {
		// create 5 tickets
		$ticket_ids = $this->create_many_paypal_tickets_basic(
			$this->num_tickets,
			$this->event_id,
			[
				'meta_input' => [
					tribe( 'tickets.handler' )->key_capacity => $this->capacity,
					'_stock'                                 => $this->stock,
					'total_sales'                            => $this->sales,
				],
			]
		);

		$this->assertNotEmpty( $ticket_ids, 'Tickets not created! ' . __METHOD__ );

		$test_data = $this->handler->get_post_totals( $this->event_id );

		// Remove a ticket.
		tribe( 'tickets.commerce.paypal' )->delete_ticket( $this->event_id, $ticket_ids[0] );

		$test_data = $this->handler->get_post_totals( $this->event_id );

		$this->assertEquals( --$this->num_tickets, $test_data['tickets'], 'Incorrect number of tickets.' );
	}

	/**
	 * @test
	 * get_post_totals() should return the correct number of pending tickets after deleting a ticket
	 *
	 * @covers Tribe__Tickets__Tickets_Handler::get_post_totals()
	 */
	public function get_post_totals_should_return_the_correct_number_of_pending_tickets_on_delete() {
		// create 5 tickets
		$ticket_ids = $this->create_many_paypal_tickets_basic(
			$this->num_tickets,
			$this->event_id,
			[
				'meta_input' => [
					tribe( 'tickets.handler' )->key_capacity => $this->capacity,
					'_stock'                                 => $this->stock,
					'total_sales'                            => $this->sales,
				],
			]
		);

		$this->assertNotEmpty( $ticket_ids, 'Tickets not created! ' . __METHOD__ );

		$test_data = $this->handler->get_post_totals( $this->event_id );

		// Remove a ticket.
		tribe( 'tickets.commerce.paypal' )->delete_ticket( $this->event_id, $ticket_ids[0] );

		$test_data = $this->handler->get_post_totals( $this->event_id );

		$this->assertEquals( 0, $test_data['pending'], 'Incorrect total pending.' );
	}

	/**
	 * @test
	 * get_post_totals() should return the correct total capacity after deleting a ticket
	 *
	 * @covers Tribe__Tickets__Tickets_Handler::get_post_totals()
	 */
	public function get_post_totals_should_return_the_correct_total_capacity_on_delete() {
		// create 5 tickets
		$ticket_ids = $this->create_many_paypal_tickets_basic(
			$this->num_tickets,
			$this->event_id,
			[
				'meta_input' => [
					tribe( 'tickets.handler' )->key_capacity => $this->capacity,
					'_stock'                                 => $this->stock,
					'total_sales'                            => $this->sales,
				],
			]
		);

		$this->assertNotEmpty( $ticket_ids, 'Tickets not created! ' . __METHOD__ );

		$test_data = $this->handler->get_post_totals( $this->event_id );

		// Remove a ticket.
		tribe( 'tickets.commerce.paypal' )->delete_ticket( $this->event_id, $ticket_ids[0] );

		$test_data = $this->handler->get_post_totals( $this->event_id );

		$this->assertEquals( ( --$this->num_tickets * $this->capacity ), $test_data['capacity'], 'Incorrect total capacity.' );
	}

	/**
	 * @test
	 * get_post_totals() should return the correct total stock after deleting a ticket
	 *
	 * @covers Tribe__Tickets__Tickets_Handler::get_post_totals()
	 */
	public function get_post_totals_should_return_the_correct_total_stock_on_delete() {
		// create 5 tickets
		$ticket_ids = $this->create_many_paypal_tickets_basic(
			$this->num_tickets,
			$this->event_id,
			[
				'meta_input' => [
					tribe( 'tickets.handler' )->key_capacity => $this->capacity,
					'_stock'                                 => $this->stock,
					'total_sales'                            => $this->sales,
				],
			]
		);

		$this->assertNotEmpty( $ticket_ids, 'Tickets not created! ' . __METHOD__ );

		$test_data = $this->handler->get_post_totals( $this->event_id );

		// Remove a ticket.
		tribe( 'tickets.commerce.paypal' )->delete_ticket( $this->event_id, $ticket_ids[0] );

		$test_data = $this->handler->get_post_totals( $this->event_id );

		$this->assertEquals( ( --$this->num_tickets * $this->stock ), $test_data['stock'], 'Incorrect total stock.' );
	}

	/**
	 * @test
	 * get_post_totals() should return the correct total sales after deleting a ticket
	 *
	 * @covers Tribe__Tickets__Tickets_Handler::get_post_totals()
	 */
	public function get_post_totals_should_return_the_correct_total_sales_on_delete() {
		// create 5 tickets
		$ticket_ids = $this->create_many_paypal_tickets_basic(
			$this->num_tickets,
			$this->event_id,
			[
				'meta_input' => [
					'_capacity'   => $this->capacity,
					'_stock'      => $this->stock,
					'total_sales' => $this->sales,
				],
			]
		);

		$this->assertNotEmpty( $ticket_ids, 'Tickets not created! ' . __METHOD__ );

		$test_data = $this->handler->get_post_totals( $this->event_id );

		// Remove a ticket.
		tribe( 'tickets.commerce.paypal' )->delete_ticket( $this->event_id, $ticket_ids[0] );

		$test_data = $this->handler->get_post_totals( $this->event_id );

		$this->assertEquals( ( --$this->num_tickets * $this->sales ), $test_data['sold'], 'Incorrect total sales.' );
	}

	/**
	 * @test
	 * get_post_totals() should detect unlimited tickets appropriately after deleting a ticket
	 *
	 * @covers Tribe__Tickets__Tickets_Handler::get_post_totals()
	 */
	public function get_post_totals_should_detect_unlimited_tickets_appropriately_on_delete() {
		$ticket_id = $this->create_paypal_ticket_basic(
			$this->event_id,
			1,
			[
				'meta_input' => [
					'_capacity'     => 3,
					'total_sales'   => 2,
				],
			]
		);

		$ticket_id_b = $this->create_paypal_ticket_basic(
			$this->event_id,
			1,
			[
				'meta_input' => [
					tribe( 'tickets.handler' )->key_capacity => -1,
					'total_sales'                            => 2,
				],
			]
		);

		// Remove a ticket.
		tribe( 'tickets.commerce.paypal' )->delete_ticket( $this->event_id, $ticket_id );

		$test_data = $this->handler->get_post_totals( $this->event_id );

		// Test for existing unlimited ticket
		$this->assertTrue( $test_data['has_unlimited'], 'Did not detect existing unlimited tickets.' );
		$this->assertEquals( -1, $test_data['capacity'], 'Incorrect total capacity on unlimited tickets.' );
	}

	/**
	 * @test
	 * get_post_totals() should detect unlimited tickets appropriately after deleting a ticket
	 *
	 * @covers Tribe__Tickets__Tickets_Handler::get_post_totals()
	 */
	public function get_post_totals_should_not_detect_deleted_unlimited_tickets() {
		$ticket_id = $this->create_paypal_ticket_basic(
			$this->event_id,
			1,
			[
				'meta_input' => [
					'_capacity'     => 3,
					'total_sales'   => 2,
				],
			]
		);

		$ticket_id_b = $this->create_paypal_ticket_basic(
			$this->event_id,
			1,
			[
				'meta_input' => [
					tribe( 'tickets.handler' )->key_capacity => -1,
					'total_sales'                            => 2,
				],
			]
		);

		// Remove a ticket.
		tribe( 'tickets.commerce.paypal' )->delete_ticket( $this->event_id, $ticket_id_b );

		$test_data = $this->handler->get_post_totals( $this->event_id );

		// Test for existing unlimited ticket
		$this->assertFalse( $test_data['has_unlimited'], 'Incorrect detection of deleted unlimited tickets.' );
	}

	/**
	 * @test
	 * It should detect shared ticket for an event with global stock.
	 *
	 * @covers Tribe__Tickets__Tickets_Handler::get_post_totals()
	 */
	public function it_should_detect_shared_ticket_for_an_event_with_global_stock() {
		$ticket_ids = $this->create_distinct_paypal_tickets_basic(
			$this->event_id,
			[
				[
					'meta_input' => [
						'_capacity'                     => 20,
						'total_sales'                   => 5,
						Global_Stock::TICKET_STOCK_MODE => Global_Stock::CAPPED_STOCK_MODE,
					],
				],
				[
					'meta_input' => [
						'_capacity'                     => 30,
						'total_sales'                   => 5,
						Global_Stock::TICKET_STOCK_MODE => Global_Stock::GLOBAL_STOCK_MODE,
					],
				],
				[
					'meta_input' => [
						'_capacity'                     => 30,
						'total_sales'                   => 5,
						Global_Stock::TICKET_STOCK_MODE => Global_Stock::OWN_STOCK_MODE,
					],
				],
			]
		);

		$test_data = $this->handler->get_post_totals( $this->event_id );

		$this->assertTrue( $test_data['has_shared'], 'Incorrect total capacity on global stock tickets.' );
	}

	/* Shared Ticket Tests */

	/**
	 * @test
	 * It should handle "capacity" on tickets with global stock correctly.
	 *
	 * @covers Tribe__Tickets__Tickets_Handler::get_post_totals()
	 */
	public function it_should_handle_capacity_on_tickets_with_global_stock() {
		$ticket_ids = $this->create_distinct_paypal_tickets_basic(
			$this->event_id,
			[
				[
					'meta_input' => [
						'_capacity'                     => 20,
						'total_sales'                   => 5,
						Global_Stock::TICKET_STOCK_MODE => Global_Stock::CAPPED_STOCK_MODE,
					],
				],
				[
					'meta_input' => [
						'_capacity'                     => 30,
						'total_sales'                   => 5,
						Global_Stock::TICKET_STOCK_MODE => Global_Stock::GLOBAL_STOCK_MODE,
					],
				],
			]
		);

		$test_data = $this->handler->get_post_totals( $this->event_id );

		$this->assertEquals( 30, $test_data['capacity'], 'Incorrect total capacity on global stock tickets.' );
	}

	/**
	 * @test
	 * It should handle "stock" on tickets with global stock correctly.
	 *
	 * @covers Tribe__Tickets__Tickets_Handler::get_post_totals()
	 */
	public function it_should_handle_stock_on_tickets_with_global_stock() {
		$ticket_ids = $this->create_distinct_paypal_tickets_basic(
			$this->event_id,
			[
				[
					'meta_input' => [
						'_capacity'                     => 20,
						'total_sales'                   => 5,
						Global_Stock::TICKET_STOCK_MODE => Global_Stock::CAPPED_STOCK_MODE,
					],
				],
				[
					'meta_input' => [
						'_capacity'                     => 30,
						'total_sales'                   => 5,
						Global_Stock::TICKET_STOCK_MODE => Global_Stock::GLOBAL_STOCK_MODE,
					],
				],
			]
		);

		$test_data = $this->handler->get_post_totals( $this->event_id );
		// Note for math: 30 is global cap, 10 sales.
		$this->assertEquals( 20, $test_data['stock'], 'Incorrect total capacity on global stock tickets.' );
	}

	/**
	 * @test
	 * It should handle "sales" on tickets with global stock correctly.
	 *
	 * @covers Tribe__Tickets__Tickets_Handler::get_post_totals()
	 */
	public function it_should_handle_sales_on_tickets_with_global_stock() {
		$ticket_ids = $this->create_distinct_paypal_tickets_basic(
			$this->event_id,
			[
				[
					'meta_input' => [
						'_capacity'                     => 20,
						'total_sales'                   => 5,
						Global_Stock::TICKET_STOCK_MODE => Global_Stock::CAPPED_STOCK_MODE,
					],
				],
				[
					'meta_input' => [
						'_capacity'                     => 30,
						'total_sales'                   => 5,
						Global_Stock::TICKET_STOCK_MODE => Global_Stock::GLOBAL_STOCK_MODE,
					],
				],
			]
		);

		$test_data = $this->handler->get_post_totals( $this->event_id );

		$this->assertEquals( 10, $test_data['sold'], 'Incorrect total capacity on global stock tickets.' );
	}

	/**
	 * @test
	 * It should handle unlimited tickets in the presence of global stock correctly.
	 *
	 * @covers Tribe__Tickets__Tickets_Handler::get_post_totals()
	 */
	public function it_should_handle_unlimited_tickets_in_the_presence_of_global_stock() {
		$ticket_ids = $this->create_distinct_paypal_tickets_basic(
			$this->event_id,
			[
				[
					'meta_input' => [
						'_capacity'                     => 20,
						'total_sales'                   => 5,
						Global_Stock::TICKET_STOCK_MODE => Global_Stock::CAPPED_STOCK_MODE,
					],
				],
				[
					'meta_input' => [
						'_capacity'                     => 30,
						'total_sales'                   => 5,
						Global_Stock::TICKET_STOCK_MODE => Global_Stock::GLOBAL_STOCK_MODE,
					],
				],
				[
					'meta_input' => [
						'_capacity'                     => -1,
						'total_sales'                   => 5,
					],
				],
			]
		);

		$test_data = $this->handler->get_post_totals( $this->event_id );

		$this->assertTrue( $test_data['has_unlimited'], 'Did not detect existing unlimited ticket.' );
		$this->assertEquals( -1, $test_data['capacity'], 'Incorrect total capacity on unlimited + global stock tickets.' );
	}



	/**
	 * @test
	 * get_post_totals()  should return the correct number of tickets initially with tickets and rsvps
	 *
	 * @covers Tribe__Tickets__Tickets_Handler::get_post_totals()
	 */
	 public function get_post_totals_should_return_the_correct_number_of_tickets_initially_with_tickets_and_rsvps() {
		// create 5 tickets
		$ticket_ids = $this->create_many_paypal_tickets_basic(
			$this->num_tickets,
			$this->event_id,
			[
				'meta_input' => [
					tribe( 'tickets.handler' )->key_capacity => $this->capacity,
					'_stock'                                 => $this->stock,
					'total_sales'                            => $this->sales,
				],
			]
		);

		$rsvp_ids = $this->create_many_paypal_tickets_basic(
			$this->num_tickets,
			$this->event_id,
			[
				'meta_input' => [
					tribe( 'tickets.handler' )->key_capacity => $this->capacity,
					'_stock'                                 => $this->stock,
					'total_sales'                            => $this->sales,
				],
			]
		);

		$this->assertNotEmpty( $ticket_ids, 'Tickets not created! ' . __METHOD__ );

		$test_data = $this->handler->get_post_totals( $this->event_id );

		$this->assertEquals( $this->num_tickets * 2, $test_data['tickets'], 'Incorrect number of tickets.' );
	}
}
