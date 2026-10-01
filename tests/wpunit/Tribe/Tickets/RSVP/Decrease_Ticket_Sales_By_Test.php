<?php

namespace Tribe\Tickets\RSVP;

use Codeception\TestCase\WPTestCase;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker;
use Tribe__Tickets__RSVP as RSVP;

class Decrease_Ticket_Sales_By_Test extends WPTestCase {
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
	 * It should decrease sales using atomic repository method
	 *
	 * @test
	 */
	public function should_decrease_sales_using_atomic_repository_method(): void {
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
				'_stock'      => 5,
			],
		] );

		$new_sales = $this->rsvp->decrease_ticket_sales_by( $ticket_id, 1 );

		$this->assertEquals( 4, $new_sales );
		$this->assertEquals( 4, get_post_meta( $ticket_id, 'total_sales', true ) );
	}

	/**
	 * It should increase stock when sales decrease
	 *
	 * @test
	 */
	public function should_increase_stock_when_sales_decrease(): void {
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
				'total_sales' => 10,
				'_capacity'   => 10, // capacity of 10 with 10 sales = 0 stock
			],
		] );

		$this->rsvp->decrease_ticket_sales_by( $ticket_id, 2 );

		$this->assertEquals( 8, get_post_meta( $ticket_id, 'total_sales', true ) );
		$this->assertEquals( 2, get_post_meta( $ticket_id, '_stock', true ) );
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
				'total_sales' => 10,
				'_stock'      => 5,
			],
		] );

		$new_sales = $this->rsvp->decrease_ticket_sales_by( $ticket_id, 3 );

		$this->assertEquals( 7, $new_sales );
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
				'total_sales' => 50,
				'_stock'      => 50,
			],
		] );

		$new_sales = $this->rsvp->decrease_ticket_sales_by( $ticket_id, 10 );

		$this->assertEquals( 40, $new_sales );
		$this->assertEquals( 40, get_post_meta( $ticket_id, 'total_sales', true ) );
		$this->assertEquals( 60, get_post_meta( $ticket_id, '_stock', true ) );
	}

	/**
	 * It should prevent negative sales
	 *
	 * @test
	 */
	public function should_prevent_negative_sales(): void {
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
				'total_sales' => 3,
				'_stock'      => 7,
			],
		] );

		// Try to decrease by more than current sales
		$new_sales = $this->rsvp->decrease_ticket_sales_by( $ticket_id, 5 );

		// Sales should be 0, not negative
		$this->assertEquals( 0, $new_sales );
		$this->assertEquals( 0, get_post_meta( $ticket_id, 'total_sales', true ) );
	}

	/**
	 * It should handle non-existent ticket
	 *
	 * @test
	 */
	public function should_handle_non_existent_ticket(): void {
		$result = $this->rsvp->decrease_ticket_sales_by( 999999, 1 );

		$this->assertFalse( $result );
	}

	/**
	 * It should handle refund scenario
	 *
	 * @test
	 */
	public function should_handle_refund_scenario(): void {
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
				'total_sales' => 15,
				'_stock'      => 5,
				'_capacity'   => 20,
			],
		] );

		// Simulate a refund
		$new_sales = $this->rsvp->decrease_ticket_sales_by( $ticket_id, 1 );

		$this->assertEquals( 14, $new_sales );
		$this->assertEquals( 14, get_post_meta( $ticket_id, 'total_sales', true ) );
		$this->assertEquals( 6, get_post_meta( $ticket_id, '_stock', true ) );
	}

	/**
	 * It should handle multiple consecutive decreases
	 *
	 * @test
	 */
	public function should_handle_multiple_consecutive_decreases(): void {
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
				'total_sales' => 20,
				'_capacity'   => 50, // capacity of 50 with 20 sales = 30 stock
			],
		] );

		$this->rsvp->decrease_ticket_sales_by( $ticket_id, 2 );
		$this->rsvp->decrease_ticket_sales_by( $ticket_id, 3 );
		$final_sales = $this->rsvp->decrease_ticket_sales_by( $ticket_id, 1 );

		$this->assertEquals( 14, $final_sales );
		$this->assertEquals( 14, get_post_meta( $ticket_id, 'total_sales', true ) );
		$this->assertEquals( 36, get_post_meta( $ticket_id, '_stock', true ) );
	}
}
