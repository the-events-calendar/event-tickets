<?php

namespace Tribe\Tickets\RSVP;

use Codeception\TestCase\WPTestCase;
use Tribe\Tickets\Test\Commerce\RSVP\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker;
use Tribe__Tickets__RSVP as RSVP;

class Update_Sales_And_Stock_By_Order_Status_Test extends WPTestCase {
	use Ticket_Maker;
	use Attendee_Maker;

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

		// Set up tickets view for RSVP options
		$tickets_view = new \Tribe__Tickets__Tickets_View();
		$this->rsvp->set_tickets_view( $tickets_view );
	}

	/**
	 * It should calculate correct delta for status changes
	 *
	 * @test
	 */
	public function should_calculate_correct_delta_for_status_changes(): void {
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

		// Create an attendee with 'no' status
		$attendee_id = $this->create_rsvp_attendee( $ticket_id, $event_id, [
			'meta_input' => [
				'_tribe_rsvp_status' => 'no',
			],
		] );

		// Change status from 'no' to 'yes' (should increase sales by 1)
		$result = $this->rsvp->update_sales_and_stock_by_order_status( $attendee_id, 'yes', $ticket_id );

		$this->assertTrue( $result );
		$this->assertEquals( 6, get_post_meta( $ticket_id, 'total_sales', true ) );
		$this->assertEquals( 4, get_post_meta( $ticket_id, '_stock', true ) );
	}

	/**
	 * It should use atomic repository method
	 *
	 * @test
	 */
	public function should_use_atomic_repository_method(): void {
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
				'_stock'      => 10,
			],
		] );

		$attendee_id = $this->create_rsvp_attendee( $ticket_id, $event_id, [
			'meta_input' => [
				'_tribe_rsvp_status' => 'yes',
			],
		] );

		// Change from 'yes' to 'no' (should decrease sales by 1)
		$result = $this->rsvp->update_sales_and_stock_by_order_status( $attendee_id, 'no', $ticket_id );

		$this->assertTrue( $result );
		$this->assertEquals( 9, get_post_meta( $ticket_id, 'total_sales', true ) );
		$this->assertEquals( 11, get_post_meta( $ticket_id, '_stock', true ) );
	}

	/**
	 * It should return boolean
	 *
	 * @test
	 */
	public function should_return_boolean(): void {
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

		$attendee_id = $this->create_rsvp_attendee( $ticket_id, $event_id, [
			'meta_input' => [
				'_tribe_rsvp_status' => 'no',
			],
		] );

		$result = $this->rsvp->update_sales_and_stock_by_order_status( $attendee_id, 'yes', $ticket_id );

		$this->assertIsBool( $result );
	}

	/**
	 * It should handle no-change scenarios (delta = 0)
	 *
	 * @test
	 */
	public function should_handle_no_change_scenarios(): void {
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
				'total_sales' => 7,
				'_stock'      => 3,
			],
		] );

		$attendee_id = $this->create_rsvp_attendee( $ticket_id, $event_id, [
			'meta_input' => [
				'_tribe_rsvp_status' => 'yes',
			],
		] );

		// Update with same status (should return true but not change anything)
		$result = $this->rsvp->update_sales_and_stock_by_order_status( $attendee_id, 'yes', $ticket_id );

		$this->assertTrue( $result );
		// Sales and stock should remain unchanged
		$this->assertEquals( 7, get_post_meta( $ticket_id, 'total_sales', true ) );
		$this->assertEquals( 3, get_post_meta( $ticket_id, '_stock', true ) );
	}

	/**
	 * It should handle status change from yes to no
	 *
	 * @test
	 */
	public function should_handle_status_change_from_yes_to_no(): void {
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
				'_stock'      => 2,
			],
		] );

		$attendee_id = $this->create_rsvp_attendee( $ticket_id, $event_id, [
			'meta_input' => [
				'_tribe_rsvp_status' => 'yes',
			],
		] );

		// Change from 'yes' to 'no'
		$result = $this->rsvp->update_sales_and_stock_by_order_status( $attendee_id, 'no', $ticket_id );

		$this->assertTrue( $result );
		$this->assertEquals( 7, get_post_meta( $ticket_id, 'total_sales', true ) );
		$this->assertEquals( 3, get_post_meta( $ticket_id, '_stock', true ) );
	}

	/**
	 * It should handle status change from no to yes
	 *
	 * @test
	 */
	public function should_handle_status_change_from_no_to_yes(): void {
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

		$attendee_id = $this->create_rsvp_attendee( $ticket_id, $event_id, [
			'meta_input' => [
				'_tribe_rsvp_status' => 'no',
			],
		] );

		// Change from 'no' to 'yes'
		$result = $this->rsvp->update_sales_and_stock_by_order_status( $attendee_id, 'yes', $ticket_id );

		$this->assertTrue( $result );
		$this->assertEquals( 6, get_post_meta( $ticket_id, 'total_sales', true ) );
		$this->assertEquals( 4, get_post_meta( $ticket_id, '_stock', true ) );
	}

	/**
	 * It should maintain atomicity with multiple status changes
	 *
	 * @test
	 */
	public function should_maintain_atomicity_with_multiple_status_changes(): void {
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

		// Create multiple attendees
		$attendee1 = $this->create_rsvp_attendee( $ticket_id, $event_id, [
			'meta_input' => [
				'_tribe_rsvp_status' => 'no',
			],
		] );
		$attendee2 = $this->create_rsvp_attendee( $ticket_id, $event_id, [
			'meta_input' => [
				'_tribe_rsvp_status' => 'no',
			],
		] );

		// Change both to 'yes'
		$this->rsvp->update_sales_and_stock_by_order_status( $attendee1, 'yes', $ticket_id );
		$this->rsvp->update_sales_and_stock_by_order_status( $attendee2, 'yes', $ticket_id );

		$this->assertEquals( 2, get_post_meta( $ticket_id, 'total_sales', true ) );
		$this->assertEquals( 8, get_post_meta( $ticket_id, '_stock', true ) );
	}
}
