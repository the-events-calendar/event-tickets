<?php

namespace Tribe\Tickets\RSVP;

use Codeception\TestCase\WPTestCase;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker;
use Tribe__Tickets__RSVP as RSVP;

class Increase_Ticket_Sales_By_Test extends WPTestCase {
	use Ticket_Maker;

	/**
	 * @var RSVP
	 */
	protected $rsvp;

	/**
	 * {@inheritdoc}
	 */
	public function setUp(): void {
		parent::setUp();
		$this->rsvp = tribe( RSVP::class );
	}

	/**
	 * It should increase sales using atomic repository method
	 *
	 * @test
	 */
	public function should_increase_sales_using_atomic_repository_method(): void {
		$event_id = tribe_events()->set_args(
			[
				'title'      => 'Test Event',
				'status'     => 'publish',
				'start_date' => '2023-01-01 00:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;
		$ticket_id = $this->create_rsvp_ticket( $event_id, [
			'meta_input' => [
				'total_sales' => 0,
				'_stock'      => 10,
			],
		] );

		$new_sales = $this->rsvp->increase_ticket_sales_by( $ticket_id, 1 );

		$this->assertEquals( 1, $new_sales );
		$this->assertEquals( 1, get_post_meta( $ticket_id, 'total_sales', true ) );
	}

	/**
	 * It should decrease stock when sales increase
	 *
	 * @test
	 */
	public function should_decrease_stock_when_sales_increase(): void {
		$event_id = tribe_events()->set_args(
			[
				'title'      => 'Test Event',
				'status'     => 'publish',
				'start_date' => '2023-01-01 00:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;
		$ticket_id = $this->create_rsvp_ticket( $event_id, [
			'meta_input' => [
				'total_sales' => 0,
				'_capacity'   => 10,
			],
		] );

		$this->rsvp->increase_ticket_sales_by( $ticket_id, 2 );

		$this->assertEquals( 2, get_post_meta( $ticket_id, 'total_sales', true ) );
		$this->assertEquals( 8, get_post_meta( $ticket_id, '_stock', true ) );
	}

	/**
	 * It should return new sales count
	 *
	 * @test
	 */
	public function should_return_new_sales_count(): void {
		$event_id = tribe_events()->set_args(
			[
				'title'      => 'Test Event',
				'status'     => 'publish',
				'start_date' => '2023-01-01 00:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;
		$ticket_id = $this->create_rsvp_ticket( $event_id, [
			'meta_input' => [
				'total_sales' => 5,
				'_stock'      => 15,
			],
		] );

		$new_sales = $this->rsvp->increase_ticket_sales_by( $ticket_id, 3 );

		$this->assertEquals( 8, $new_sales );
	}

	/**
	 * It should handle quantity greater than 1
	 *
	 * @test
	 */
	public function should_handle_quantity_greater_than_1(): void {
		$event_id = tribe_events()->set_args(
			[
				'title'      => 'Test Event',
				'status'     => 'publish',
				'start_date' => '2023-01-01 00:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;
		$ticket_id = $this->create_rsvp_ticket( $event_id, [
			'meta_input' => [
				'total_sales' => 0,
				'_stock'      => 100,
			],
		] );

		$new_sales = $this->rsvp->increase_ticket_sales_by( $ticket_id, 10 );

		$this->assertEquals( 10, $new_sales );
		$this->assertEquals( 10, get_post_meta( $ticket_id, 'total_sales', true ) );
		$this->assertEquals( 90, get_post_meta( $ticket_id, '_stock', true ) );
	}

	/**
	 * It should handle non-existent ticket
	 *
	 * @test
	 */
	public function should_handle_non_existent_ticket(): void {
		$result = $this->rsvp->increase_ticket_sales_by( 999999, 1 );

		$this->assertFalse( $result );
	}

	/**
	 * It should prevent stock from going negative
	 *
	 * @test
	 */
	public function should_prevent_stock_from_going_negative(): void {
		$event_id = tribe_events()->set_args(
			[
				'title'      => 'Test Event',
				'status'     => 'publish',
				'start_date' => '2023-01-01 00:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;
		$ticket_id = $this->create_rsvp_ticket( $event_id, [
			'meta_input' => [
				'total_sales' => 8,
				'_capacity'   => 10, // capacity of 10 with 8 sales = 2 stock
			],
		] );

		// Try to increase by more than available stock
		$new_sales = $this->rsvp->increase_ticket_sales_by( $ticket_id, 5 );

		// For RSVP tickets, sales can exceed capacity (10), so it should be 13.
		$this->assertEquals( 13, $new_sales );
		// Stock should be 0, not negative
		$this->assertEquals( 0, get_post_meta( $ticket_id, '_stock', true ) );
	}

	/**
	 * It should handle tickets with no initial sales
	 *
	 * @test
	 */
	public function should_handle_tickets_with_no_initial_sales(): void {
		$event_id = tribe_events()->set_args(
			[
				'title'      => 'Test Event',
				'status'     => 'publish',
				'start_date' => '2023-01-01 00:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;
		$ticket_id = $this->create_rsvp_ticket( $event_id, [
			'meta_input' => [
				'_stock' => 50,
			],
		] );

		// Don't set total_sales - should default to 0
		delete_post_meta( $ticket_id, 'total_sales' );

		$new_sales = $this->rsvp->increase_ticket_sales_by( $ticket_id, 1 );

		$this->assertEquals( 1, $new_sales );
		$this->assertEquals( 1, get_post_meta( $ticket_id, 'total_sales', true ) );
	}
}
