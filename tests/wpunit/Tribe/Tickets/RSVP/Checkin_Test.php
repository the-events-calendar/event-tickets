<?php

namespace Tribe\Tickets\RSVP;

use Codeception\TestCase\WPTestCase;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Attendee_Maker;
use Tribe__Tickets__RSVP as RSVP;

class Checkin_Test extends WPTestCase {
	use Ticket_Maker;
	use Attendee_Maker;

	/**
	 * @var RSVP
	 */
	protected $rsvp;

	/**
	 * @var int
	 */
	protected $event_id;

	/**
	 * @var int
	 */
	protected $ticket_id;

	/**
	 * @var int
	 */
	protected $attendee_id;

	/**
	 * {@inheritdoc}
	 */
	public function setUp(): void {
		parent::setUp();

		$this->rsvp = tribe( RSVP::class );

		// Create test event
		$this->event_id = tribe_events()->set_args(
			[
				'title'      => 'Test Event',
				'status'     => 'publish',
				'start_date' => '2025-12-01 00:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;

		// Create test ticket
		$this->ticket_id = $this->create_rsvp_ticket( $this->event_id );

		// Create test attendee
		$this->attendee_id = $this->create_rsvp_attendee( $this->ticket_id, $this->event_id );

		// Set the user to a user that can manage Attendees.
		wp_set_current_user(static::factory()->user->create( [ 'role' => 'administrator' ] ));
	}

	/**
	 * It should update check_in field via repository
	 *
	 * @test
	 */
	public function should_checkin_updates_check_in_field_via_repository(): void {
		// Ensure attendee is not checked in
		$repository = tribe_attendees( 'rsvp' );
		$this->assertEmpty( $repository->get_field( $this->attendee_id, 'check_in' ) );

		// Check in the attendee
		$result = $this->rsvp->checkin( $this->attendee_id );

		$this->assertTrue( $result );

		// Verify check_in field is set to 1 via repository
		$check_in = $repository->get_field( $this->attendee_id, 'check_in' );
		$this->assertEquals( 1, $check_in );
	}

	/**
	 * It should set qr_status when qr parameter is true
	 *
	 * @test
	 */
	public function should_checkin_set_qr_status_when_qr_true(): void {
		$repository = tribe_attendees( 'rsvp' );

		// Check in via QR
		$result = $this->rsvp->checkin( $this->attendee_id, true );

		$this->assertTrue( $result );

		// Verify qr_status is set
		$qr_status = $repository->get_field( $this->attendee_id, 'qr_status' );
		$this->assertEquals( 1, $qr_status );
	}

	/**
	 * It should not set qr_status when qr parameter is false
	 *
	 * @test
	 */
	public function should_checkin_not_set_qr_status_when_qr_false(): void {
		$repository = tribe_attendees( 'rsvp' );

		// Check in without QR
		$result = $this->rsvp->checkin( $this->attendee_id, false );

		$this->assertTrue( $result );

		// Verify qr_status is not set
		$qr_status = $repository->get_field( $this->attendee_id, 'qr_status' );
		$this->assertEmpty( $qr_status );
	}

	/**
	 * It should respect permission checks
	 *
	 * @test
	 */
	public function should_checkin_respect_permission_checks(): void {
		// Set up a user without permission
		$user_id = $this->factory()->user->create( [ 'role' => 'subscriber' ] );
		wp_set_current_user( $user_id );

		// Mock the permission check to return false
		add_filter( 'user_has_cap', function ( $allcaps ) {
			$allcaps['edit_tribe_events'] = false;
			return $allcaps;
		}, 10, 1 );

		// Try to check in without permission (not QR)
		$result = $this->rsvp->checkin( $this->attendee_id, false );

		$this->assertFalse( $result );

		// Verify attendee is not checked in
		$repository = tribe_attendees( 'rsvp' );
		$check_in = $repository->get_field( $this->attendee_id, 'check_in' );
		$this->assertEmpty( $check_in );
	}

	/**
	 * It should fire rsvp_checkin hook
	 *
	 * @test
	 */
	public function should_checkin_fire_rsvp_checkin_hook(): void {
		$hook_fired = false;
		$hook_attendee_id = null;
		$hook_qr = null;

		add_action( 'rsvp_checkin', function ( $attendee_id, $qr ) use ( &$hook_fired, &$hook_attendee_id, &$hook_qr ) {
			$hook_fired = true;
			$hook_attendee_id = $attendee_id;
			$hook_qr = $qr;
		}, 10, 2 );

		$this->rsvp->checkin( $this->attendee_id, true );

		$this->assertTrue( $hook_fired );
		$this->assertEquals( $this->attendee_id, $hook_attendee_id );
		$this->assertTrue( $hook_qr );
	}

	/**
	 * It should fire rsvp_checkin_details filter
	 *
	 * @test
	 */
	public function should_checkin_fire_rsvp_checkin_details_filter(): void {
		$filter_fired = false;

		add_filter( 'rsvp_checkin_details', function ( $details ) use ( &$filter_fired ) {
			$filter_fired = true;
			$this->assertIsArray( $details );
			$this->assertArrayHasKey( 'date', $details );
			$this->assertArrayHasKey( 'source', $details );
			$this->assertArrayHasKey( 'author', $details );
			$this->assertArrayHasKey( 'device_id', $details );
			return $details;
		}, 10, 1 );

		$this->rsvp->checkin( $this->attendee_id );

		$this->assertTrue( $filter_fired );
	}

	/**
	 * It should store check_in_details via repository
	 *
	 * @test
	 */
	public function should_checkin_store_check_in_details_via_repository(): void {
		$repository = tribe_attendees( 'rsvp' );

		$this->rsvp->checkin( $this->attendee_id );

		$details = $repository->get_field( $this->attendee_id, 'check_in_details' );

		$this->assertIsArray( $details );
		$this->assertArrayHasKey( 'date', $details );
		$this->assertArrayHasKey( 'source', $details );
		$this->assertArrayHasKey( 'author', $details );
		$this->assertEquals( 'site', $details['source'] );
	}

	/**
	 * It should remove check_in via repository
	 *
	 * @test
	 */
	public function should_uncheckin_remove_check_in_via_repository(): void {
		$repository = tribe_attendees( 'rsvp' );

		// First check in the attendee
		$this->rsvp->checkin( $this->attendee_id );
		$this->assertEquals( 1, $repository->get_field( $this->attendee_id, 'check_in' ) );

		// Now uncheck
		$result = $this->rsvp->uncheckin( $this->attendee_id );

		$this->assertTrue( $result );

		// Verify check_in field is removed
		$check_in = $repository->get_field( $this->attendee_id, 'check_in' );
		$this->assertEmpty( $check_in );
	}

	/**
	 * It should remove check_in_details and qr_status on uncheckin
	 *
	 * @test
	 */
	public function should_uncheckin_remove_all_checkin_fields(): void {
		$repository = tribe_attendees( 'rsvp' );

		// Check in with QR
		$this->rsvp->checkin( $this->attendee_id, true );

		// Verify all fields are set
		$this->assertEquals( 1, $repository->get_field( $this->attendee_id, 'check_in' ) );
		$this->assertEquals( 1, $repository->get_field( $this->attendee_id, 'qr_status' ) );
		$this->assertNotEmpty( $repository->get_field( $this->attendee_id, 'check_in_details' ) );

		// Uncheck
		$this->rsvp->uncheckin( $this->attendee_id );

		// Verify all fields are removed
		$this->assertEmpty( $repository->get_field( $this->attendee_id, 'check_in' ) );
		$this->assertEmpty( $repository->get_field( $this->attendee_id, 'qr_status' ) );
		$this->assertEmpty( $repository->get_field( $this->attendee_id, 'check_in_details' ) );
	}

	/**
	 * It should respect permission checks on uncheckin
	 *
	 * @test
	 */
	public function should_uncheckin_respect_permission_checks(): void {
		// First check in the attendee as admin
		$this->rsvp->checkin( $this->attendee_id );

		// Set up a user without permission
		$user_id = $this->factory()->user->create( [ 'role' => 'subscriber' ] );
		wp_set_current_user( $user_id );

		// Mock the permission check to return false
		add_filter( 'user_has_cap', function ( $allcaps ) {
			$allcaps['edit_tribe_events'] = false;
			return $allcaps;
		}, 10, 1 );

		// Try to uncheck without permission
		$result = $this->rsvp->uncheckin( $this->attendee_id );

		$this->assertFalse( $result );

		// Verify attendee is still checked in
		$repository = tribe_attendees( 'rsvp' );
		$check_in = $repository->get_field( $this->attendee_id, 'check_in' );
		$this->assertEquals( 1, $check_in );
	}

	/**
	 * It should fire rsvp_uncheckin hook
	 *
	 * @test
	 */
	public function should_uncheckin_fire_rsvp_uncheckin_hook(): void {
		$hook_fired = false;
		$hook_attendee_id = null;

		add_action( 'rsvp_uncheckin', function ( $attendee_id ) use ( &$hook_fired, &$hook_attendee_id ) {
			$hook_fired = true;
			$hook_attendee_id = $attendee_id;
		}, 10, 1 );

		// First check in
		$this->rsvp->checkin( $this->attendee_id );

		// Now uncheck
		$this->rsvp->uncheckin( $this->attendee_id );

		$this->assertTrue( $hook_fired );
		$this->assertEquals( $this->attendee_id, $hook_attendee_id );
	}

	/**
	 * It should increment ticket_sent counter via repository
	 *
	 * @test
	 */
	public function should_update_ticket_sent_counter_increment_via_repository(): void {
		// Create attendee with ticket_sent explicitly set to 0
		$attendee_id = $this->create_rsvp_attendee( $this->ticket_id, $this->event_id, [ 'ticket_sent' => 0 ] );
		$repository = tribe_attendees( 'rsvp' );

		// Initial value should be 0
		$initial = (int) $repository->get_field( $attendee_id, 'ticket_sent' );
		$this->assertEquals( 0, $initial );

		// Update counter
		$this->rsvp->update_ticket_sent_counter( $attendee_id );

		// Verify it incremented
		$after_first = (int) $repository->get_field( $attendee_id, 'ticket_sent' );
		$this->assertEquals( 1, $after_first );

		// Update again
		$this->rsvp->update_ticket_sent_counter( $attendee_id );

		// Verify it incremented again
		$after_second = (int) $repository->get_field( $attendee_id, 'ticket_sent' );
		$this->assertEquals( 2, $after_second );
	}

	/**
	 * It should start ticket_sent counter at zero
	 *
	 * @test
	 */
	public function should_update_ticket_sent_counter_start_at_zero(): void {
		// Create attendee with ticket_sent explicitly set to false
		$attendee_id = $this->create_rsvp_attendee( $this->ticket_id, $this->event_id, [ 'ticket_sent' => false ] );
		$repository = tribe_attendees( 'rsvp' );

		// Ensure field is empty initially
		$initial = $repository->get_field( $attendee_id, 'ticket_sent' );
		$this->assertEmpty( $initial );

		// Update counter from empty state
		$this->rsvp->update_ticket_sent_counter( $attendee_id );

		// Verify it's now 1
		$current = (int) $repository->get_field( $attendee_id, 'ticket_sent' );
		$this->assertEquals( 1, $current );
	}

	/**
	 * It should append entries to activity log via repository
	 *
	 * @test
	 */
	public function should_update_attendee_activity_log_append_entries_via_repository(): void {
		$repository = tribe_attendees( 'rsvp' );

		// Add first log entry
		$this->rsvp->update_attendee_activity_log( $this->attendee_id, [ 'action' => 'test_action_1' ] );

		$activity = $repository->get_field( $this->attendee_id, 'activity_log' );
		$this->assertIsArray( $activity );
		$this->assertCount( 1, $activity );
		$this->assertEquals( 'test_action_1', $activity[0]['action'] );
		$this->assertArrayHasKey( 'time', $activity[0] );

		// Add second log entry
		$this->rsvp->update_attendee_activity_log( $this->attendee_id, [ 'action' => 'test_action_2' ] );

		$activity = $repository->get_field( $this->attendee_id, 'activity_log' );
		$this->assertIsArray( $activity );
		$this->assertCount( 2, $activity );
		$this->assertEquals( 'test_action_1', $activity[0]['action'] );
		$this->assertEquals( 'test_action_2', $activity[1]['action'] );
	}

	/**
	 * It should fire filter hook for activity log
	 *
	 * @test
	 */
	public function should_update_attendee_activity_log_fire_filter_hook(): void {
		$filter_fired = false;

		add_filter( 'tribe_tickets_attendee_activity_log_data', function ( $data, $attendee_id ) use ( &$filter_fired ) {
			$filter_fired = true;
			$this->assertIsArray( $data );
			$this->assertEquals( $this->attendee_id, $attendee_id );
			return $data;
		}, 10, 2 );

		$this->rsvp->update_attendee_activity_log( $this->attendee_id, [ 'action' => 'test' ] );

		$this->assertTrue( $filter_fired );
	}

	/**
	 * It should handle empty activity log gracefully
	 *
	 * @test
	 */
	public function should_update_attendee_activity_log_handle_empty_log(): void {
		$repository = tribe_attendees( 'rsvp' );

		// Ensure log is empty
		$initial = $repository->get_field( $this->attendee_id, 'activity_log' );
		$this->assertEmpty( $initial );

		// Add first entry
		$this->rsvp->update_attendee_activity_log( $this->attendee_id, [ 'action' => 'first' ] );

		$activity = $repository->get_field( $this->attendee_id, 'activity_log' );
		$this->assertIsArray( $activity );
		$this->assertCount( 1, $activity );
	}
}
