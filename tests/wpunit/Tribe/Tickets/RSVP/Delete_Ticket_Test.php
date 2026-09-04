<?php

namespace Tribe\Tickets\RSVP;

use Codeception\TestCase\WPTestCase;
use Tribe\Tickets\Test\Commerce\RSVP\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker;
use Tribe__Tickets__RSVP as RSVP;

class Delete_Ticket_Test extends WPTestCase {
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

		// Set the current user to an administrator to make sure they will have the permission to edit tickets, attendees.
		wp_set_current_user(static::factory()->user->create(['role' => 'administrator']));
	}

	/**
	 * It should delete ticket via repository
	 *
	 * @test
	 */
	public function should_delete_ticket_via_repository(): void {
		$event_id = tribe_events()->set_args(
			[
				'title'      => 'Test Event',
				'status'     => 'publish',
				'start_date' => '2023-01-01 00:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;
		$ticket_id = $this->create_rsvp_ticket( $event_id );

		$result = $this->rsvp->delete_ticket( $event_id, $ticket_id );

		$this->assertTrue( $result );
		// Ticket should be deleted immediately (not trashed)
		$this->assertFalse( get_post_status( $ticket_id ) );
	}

	/**
	 * It should mark orphaned attendees with deleted product name
	 *
	 * @test
	 */
	public function should_mark_orphaned_attendees_with_deleted_product_name(): void {
		$event_id = tribe_events()->set_args(
			[
				'title'      => 'Test Event',
				'status'     => 'publish',
				'start_date' => '2023-01-01 00:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;
		$ticket_id = $this->create_rsvp_ticket( $event_id, [
			'post_title' => 'VIP Ticket',
		] );

		// Create attendees for this ticket
		$attendee1 = $this->create_rsvp_attendee( $ticket_id, $event_id );
		$attendee2 = $this->create_rsvp_attendee( $ticket_id, $event_id );

		$result = $this->rsvp->delete_ticket( $event_id, $ticket_id );

		$this->assertTrue( $result );

		// Check that attendees have deleted product name marked
		$deleted_product1 = get_post_meta( $attendee1, '_tribe_deleted_product_name', true );
		$deleted_product2 = get_post_meta( $attendee2, '_tribe_deleted_product_name', true );

		$this->assertEquals( 'VIP Ticket', $deleted_product1 );
		$this->assertEquals( 'VIP Ticket', $deleted_product2 );
	}

	/**
	 * It should use bulk_update for attendees
	 *
	 * @test
	 */
	public function should_use_bulk_update_for_attendees(): void {
		$event_id = tribe_events()->set_args(
			[
				'title'      => 'Test Event',
				'status'     => 'publish',
				'start_date' => '2023-01-01 00:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;
		$ticket_id = $this->create_rsvp_ticket( $event_id, [
			'post_title' => 'General Admission',
		] );

		// Create multiple attendees
		$attendee_ids = [];
		for ( $i = 0; $i < 5; $i++ ) {
			$attendee_ids[] = $this->create_rsvp_attendee( $ticket_id, $event_id );
		}

		$result = $this->rsvp->delete_ticket( $event_id, $ticket_id );

		$this->assertTrue( $result );

		// All attendees should have deleted product marked
		foreach ( $attendee_ids as $attendee_id ) {
			$deleted_product = get_post_meta( $attendee_id, '_tribe_deleted_product_name', true );
			$this->assertEquals( 'General Admission', $deleted_product );
		}
	}

	/**
	 * It should call hooks
	 *
	 * @test
	 */
	public function should_call_hooks(): void {
		$event_id = tribe_events()->set_args(
			[
				'title'      => 'Test Event',
				'status'     => 'publish',
				'start_date' => '2023-01-01 00:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;
		$ticket_id = $this->create_rsvp_ticket( $event_id );

		$hook_called = false;
		$hook_ticket_id = null;
		$hook_event_id = null;

		add_action( 'tickets_rsvp_ticket_deleted', function( $tid, $eid, $pid ) use ( &$hook_called, &$hook_ticket_id, &$hook_event_id ) {
			$hook_called = true;
			$hook_ticket_id = $tid;
			$hook_event_id = $eid;
		}, 10, 3 );

		$this->rsvp->delete_ticket( $event_id, $ticket_id );

		$this->assertTrue( $hook_called );
		$this->assertEquals( $ticket_id, $hook_ticket_id );
		$this->assertEquals( $event_id, $hook_event_id );
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
		$ticket_id = $this->create_rsvp_ticket( $event_id );

		$result = $this->rsvp->delete_ticket( $event_id, $ticket_id );

		$this->assertIsBool( $result );
		$this->assertTrue( $result );
	}

	/**
	 * It should handle ticket with no attendees
	 *
	 * @test
	 */
	public function should_handle_ticket_with_no_attendees(): void {
		$event_id = tribe_events()->set_args(
			[
				'title'      => 'Test Event',
				'status'     => 'publish',
				'start_date' => '2023-01-01 00:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;
		$ticket_id = $this->create_rsvp_ticket( $event_id );

		// Don't create any attendees
		$result = $this->rsvp->delete_ticket( $event_id, $ticket_id );

		$this->assertTrue( $result );
		// Ticket should be deleted immediately (not trashed)
		$this->assertFalse( get_post_status( $ticket_id ) );
	}

	/**
	 * It should return false for non-existent ticket
	 *
	 * @test
	 */
	public function should_return_false_for_non_existent_ticket(): void {
		$event_id = tribe_events()->set_args(
			[
				'title'      => 'Test Event',
				'status'     => 'publish',
				'start_date' => '2023-01-01 00:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;

		$result = $this->rsvp->delete_ticket( $event_id, 999999 );

		$this->assertFalse( $result );
	}

	/**
	 * It should get event_id via repository if not provided
	 *
	 * @test
	 */
	public function should_get_event_id_via_repository_if_not_provided(): void {
		$event_id = tribe_events()->set_args(
			[
				'title'      => 'Test Event',
				'status'     => 'publish',
				'start_date' => '2023-01-01 00:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;
		$ticket_id = $this->create_rsvp_ticket( $event_id );

		// Call without event_id
		$result = $this->rsvp->delete_ticket( 0, $ticket_id );

		$this->assertTrue( $result );
		// Ticket should be deleted immediately (not trashed)
		$this->assertFalse( get_post_status( $ticket_id ) );
	}

	/**
	 * It should handle tickets with special characters in title
	 *
	 * @test
	 */
	public function should_handle_tickets_with_special_characters_in_title(): void {
		$event_id = tribe_events()->set_args(
			[
				'title'      => 'Test Event',
				'status'     => 'publish',
				'start_date' => '2023-01-01 00:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;
		$ticket_id = $this->create_rsvp_ticket( $event_id, [
			'post_title' => 'VIP Ticket & Special <Access>',
		] );

		$attendee_id = $this->create_rsvp_attendee( $ticket_id, $event_id );

		$result = $this->rsvp->delete_ticket( $event_id, $ticket_id );

		$this->assertTrue( $result );

		// Check that special characters are properly escaped
		$deleted_product = get_post_meta( $attendee_id, '_tribe_deleted_product_name', true );
		$this->assertNotEmpty( $deleted_product );
	}

	/**
	 * It should handle permission checks
	 *
	 * @test
	 */
	public function should_handle_permission_checks(): void {
		$event_id = tribe_events()->set_args(
			[
				'title'      => 'Test Event',
				'status'     => 'publish',
				'start_date' => '2023-01-01 00:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;
		$ticket_id = $this->create_rsvp_ticket( $event_id );

		// Test with invalid event should return false
		$result = $this->rsvp->delete_ticket( 0, 999999 );

		$this->assertFalse( $result );
	}
}
